import { dirname, resolve } from 'path';
import { mkdirSync, readdirSync, renameSync } from 'node:fs';

import { build } from 'vite';

import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));

const bundles = [
  {
    entry: resolve(__dirname, '../src/main.ts'),
    name: 'moneyMain',
    fileName: 'money-main'
  },
  {
    entry: resolve(__dirname, '../src/main-personal-settings.ts'),
    name: 'moneyPersonalSettings',
    fileName: 'money-personal-settings'
  },
  {
    entry: resolve(__dirname, '../src/main-dashboard.ts'),
    name: 'moneyDashboard',
    fileName: 'money-dashboard'
  }
];

const mode = process.env.VITE_MODE ?? 'production';

// Build each bundle sequentially: the IIFE JS bundles are written to ./js
// (referenced via Util::addScript), the emitted CSS assets are moved to ./css
// afterwards (referenced via Util::addStyle).
for (const bundle of bundles) {
  await build({
    build: {
      lib: {
        ...bundle,
        formats: ['iife']
      },
      outDir: './js',
      minify: mode === 'development' ? false : 'esbuild',
      sourcemap: mode === 'development' ? true : false,
      emptyOutDir: false,
      rollupOptions: {
        output: {
          assetFileNames: `${bundle.fileName}.css`
        }
      }
    },
    resolve: {
      alias: [
        {
          find: /vue-material-design-icons\/(.*)/,
          replacement: resolve('node_modules/vue-material-design-icons') + '/$1'
        }
      ]
    },
    plugins: [vue()],
    define: {
      'process.env.NODE_ENV': JSON.stringify(mode)
    }
  });
}

// Vite writes the CSS asset next to the JS bundle in ./js, but stylesheets are
// served from ./css - move every emitted stylesheet there. This replaces the
// old '../js/<name>' path-escape hack in the Util::addStyle call sites.
const cssDir = resolve(__dirname, '../css');
mkdirSync(cssDir, { recursive: true });
const jsDir = resolve(__dirname, '../js');
for (const file of readdirSync(jsDir)) {
  if (file.endsWith('.css')) {
    renameSync(resolve(jsDir, file), resolve(cssDir, file));
  }
}
