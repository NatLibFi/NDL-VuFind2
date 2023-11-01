package org.vufind.index;
/**
 * Full text retrieval indexing routines.
 *
 * Copyright (C) Villanova University 2017.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

import org.marc4j.marc.Record;
import java.io.*;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import org.w3c.dom.Document;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.apache.log4j.Logger;
import org.solrmarc.index.SolrIndexer;
import org.solrmarc.tools.SolrMarcIndexerException;

/**
 * Full text retrieval indexing routines.
 */
public class FullTextTools
{
    // Initialize logging category
    static Logger logger = Logger.getLogger(FullTextTools.class.getName());

    /**
     * Load configurations for the full text parser.  Return an array containing the
     * parser type in the first element and the parser configuration in the second
     * element.
     *
     * @return String[]
     */
    public String[] getFulltextParserSettings()
    {
        String parserType = ConfigManager.instance().getConfigSetting(
            "fulltext.ini", "General", "parser"
        );
        if (null != parserType) {
            parserType = parserType.toLowerCase();
        }

        // Is Aperture active?
        String aperturePath = ConfigManager.instance().getConfigSetting(
            "fulltext.ini", "Aperture", "webcrawler"
        );
        if ((null == parserType && null != aperturePath)
            || (null != parserType && parserType.equals("aperture"))
        ) {
            String[] array = { "aperture", aperturePath };
            return array;
        }

        // Is Tika active?
        String tikaPath = ConfigManager.instance().getConfigSetting(
            "fulltext.ini", "Tika", "path"
        );
        if ((null == parserType && null != tikaPath)
            || (null != parserType && parserType.equals("tika"))
        ) {
            String[] array = { "tika", tikaPath };
            return array;
        }

        // No recognized parser found:
        String[] array = { "none", null };
        return array;
    }

    /**
     * Extract full-text from the documents referenced in the tags
     *
     * @param Record record current MARC record
     * @param String field spec to search for URLs
     * @param String only harvest files matching this extension (null for all)
     * @return String The full-text
     */
    public String getFulltext(Record record, String fieldSpec, String extension) {
        String result = "";

        // Get the web crawler settings (and return no text if it is unavailable)
        String[] parserSettings = getFulltextParserSettings();
        if (parserSettings[0].equals("none")) {
            return null;
        }

        // Loop through the specified MARC fields:
        for (String raw : SolrIndexer.instance().getFieldList(record, fieldSpec)) {
            // Get the current string to work on (and sanitize spaces):
            String current = raw.replaceAll(" ", "%20");
            // Filter by file extension
            if (extension == null || current.endsWith(extension)) {
                // Load the parser output for each tag into a string
                result = result + harvestWithParser(current, parserSettings);
            }
        }
        // return string to SolrMarc
        return result;
    }

    /**
     * Extract full-text from the documents referenced in the tags
     *
     * @param Record record current MARC record
     * @param String field spec to search for URLs
     * @return String The full-text
     */
    public String getFulltext(Record record, String fieldSpec) {
        return getFulltext(record, fieldSpec, null);
    }

    /**
     * Extract full-text from the documents referenced in the tags
     *
     * @param Record record current MARC record
     * @return String The full-text
     */
    public String getFulltext(Record record) {
        return getFulltext(record, "856u", null);
    }

    /**
     * Clean up XML data generated by Aperture
     *
     * @param f file to clean
     * @return a fixed version of the file
     */
    public File sanitizeApertureOutput(File f) throws IOException
    {
        //clean up the aperture xml output
        File tempFile = File.createTempFile("buffer", ".tmp");
        FileOutputStream fw = new FileOutputStream(tempFile);
        OutputStreamWriter writer = new OutputStreamWriter(fw, "UTF8");

        //delete this control character from the File and save
        FileReader fr = new FileReader(f);
        BufferedReader br = new BufferedReader(fr);
        while (br.ready()) {
            writer.write(sanitizeFullText(br.readLine()));
        }
        writer.close();
        br.close();
        fr.close();

        return tempFile;
    }

    /**
     * Clean up bad characters in the full text.
     *
     * @param text text to clean
     * @return cleaned text
     */
    public String sanitizeFullText(String text)
    {
        String badChars = "[^\\u0009\\u000A\\u000D\\u0020-\\uD7FF\\uE000-\\uFFFD\\u10000-\\u10FFFF]+";
        return text.replaceAll(badChars, " ");
    }

    /**
     * Harvest the contents of a document file (PDF, Word, etc.) using Aperture.
     * This method will only work if Aperture is properly configured in the
     * fulltext.ini file.  Without proper configuration, this will simply return an
     * empty string.
     *
     * @param url the url extracted from the MARC tag.
     * @param aperturePath The path to Aperture
     * @return full-text extracted from url
     */
    public String harvestWithAperture(String url, String aperturePath) {
        String plainText = "";
        // Create temp file.
        File f = null;
        try {
            f = File.createTempFile("apt", ".txt");
        } catch (Throwable e) {
            dieWithError("Unable to create temporary file for full text harvest.");
        }

        // Delete temp file when program exits.
        f.deleteOnExit();

        // Construct the command to call Aperture
        String cmd = aperturePath + " -o " + f.getAbsolutePath().toString()  + " -x " + url;

        // Call Aperture
        //System.out.println("Loading fulltext from " + url + ". Please wait ...");
        try {
            Process p = Runtime.getRuntime().exec(cmd);

            // Debugging output
            /*
            BufferedReader stdInput = new BufferedReader(new
                InputStreamReader(p.getInputStream()));
            String s;
            while ((s = stdInput.readLine()) != null) {
                System.out.println(s);
            }
            */

            // Wait for Aperture to finish
            p.waitFor();
        } catch (Throwable e) {
            logger.error("Problem executing Aperture -- " + e.getMessage());
        }

        // Parse Aperture XML output
        Document xmlDoc = null;
        try {
            DocumentBuilderFactory dbf = DocumentBuilderFactory.newInstance();
            DocumentBuilder db = dbf.newDocumentBuilder();
            File tempFile = sanitizeApertureOutput(f);
            xmlDoc = db.parse(tempFile);
            NodeList nl = xmlDoc.getElementsByTagName("plainTextContent");
            if(nl != null && nl.getLength() > 0) {
                Node node = nl.item(0);
                if (node.getNodeType() == Node.ELEMENT_NODE) {
                    plainText = plainText + node.getTextContent();
                }
            }

            // we'll hold onto the temp file if it failed to parse for debugging;
            // only set it up to be deleted if we've made it this far successfully.
            tempFile.deleteOnExit();
        } catch (Throwable e) {
            logger.error("Problem parsing Aperture XML -- " + e.getMessage());
        }

        return plainText;
    }

    class ErrorStreamHandler extends Thread {
        InputStream stdErr;

        ErrorStreamHandler(InputStream stdErr) {
            this.stdErr = stdErr;
        }

        public void run()
        {
            try {
                InputStreamReader isr = new InputStreamReader(stdErr, "UTF8");
                BufferedReader br = new BufferedReader(isr);
                String line = null;
                while ((line = br.readLine()) != null) {
                    logger.debug(line);
                }
            } catch (Exception e) {
                e.printStackTrace();
            }
        }
    }

    /**
     * Harvest the contents of a document file (PDF, Word, etc.) using Tika.
     * This method will only work if Tika is properly configured in the fulltext.ini
     * file.  Without proper configuration, this will simply return an empty string.
     *
     * @param url the url extracted from the MARC tag.
     * @param scraperPath path to Tika
     * @return the full-text
     */
    public String harvestWithTika(String url, String scraperPath) {
        StringBuilder stringBuilder= new StringBuilder();

        // Call our scraper
        //System.out.println("Loading fulltext from " + url + ". Please wait ...");
        try {
            ProcessBuilder pb = new ProcessBuilder(
                "java", "-jar", scraperPath, "-t", "-eutf8", url
            );
            Process p = pb.start();
            ErrorStreamHandler esh = new ErrorStreamHandler(p.getErrorStream());
            esh.start();
            BufferedReader stdInput = new BufferedReader(new
                InputStreamReader(p.getInputStream(), "UTF8"));

            // We'll build the string from the command output
            String s;
            while ((s = stdInput.readLine()) != null) {
                stringBuilder.append(s);
            }
        } catch (Throwable e) {
            logger.error("Problem with Tika -- " + e.getMessage());
        }

        return sanitizeFullText(stringBuilder.toString());
    }

    /**
     * Harvest the contents of a document file (PDF, Word, etc.) using the active parser.
     *
     * @param url the URL extracted from the MARC tag.
     * @param settings configuration settings from {@code getFulltextParserSettings}.
     * @return the full-text
     */
    public String harvestWithParser(String url, String[] settings) {
        if (settings[0].equals("aperture")) {
            return harvestWithAperture(url, settings[1]);
        } else if (settings[0].equals("tika")) {
            return harvestWithTika(url, settings[1]);
        }
        return null;
    }

    /**
     * Log an error message and throw a fatal exception.
     * @param msg message to log
     */
    private void dieWithError(String msg)
    {
        logger.error(msg);
        throw new SolrMarcIndexerException(SolrMarcIndexerException.EXIT, msg);
    }
}