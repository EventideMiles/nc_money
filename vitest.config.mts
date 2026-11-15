import { defineConfig, configDefaults } from 'vitest/config';

import vue from '@vitejs/plugin-vue';

// Pin the test timezone to UTC (matching CI runners): date-related specs assert
// UTC-midnight Date expectations, which only hold when the environment runs in UTC.
process.env.TZ = 'UTC';

export default defineConfig({
  plugins: [vue()],
  test: {
    environment: 'jsdom',
    globals: true,
    coverage: {
      provider: 'istanbul',
      include: ['src/**/*']
    },
    exclude: [...configDefaults.exclude],
    server: {
      deps: {
        inline: ['@nextcloud/vue', '@nextcloud/dialogs']
      }
    }
  }
});
