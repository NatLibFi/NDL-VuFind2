import * as path from 'path';
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

const CLIENT_DIR = "components/react-clover";
const DIST_DIR = "vendor/clover";

// https://vite.dev/config/
export default defineConfig({
  root: path.resolve("js"),
  plugins: [react()],
  define: {"process.env.NODE_ENV": '"production"'},
  build: {
    outDir: path.resolve("js", DIST_DIR),
    emptyOutDir: true,
    manifest: true,
    minify: "esbuild",
    rollupOptions: {
      input: path.resolve("js", CLIENT_DIR, 'index.tsx'),
      treeshake: true,
    }
  }
})
