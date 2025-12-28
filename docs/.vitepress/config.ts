import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'Pest Plugin Bridge',
  description: 'Browser testing for external frontend applications with Pest PHP',

  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/logo.svg' }]
  ],

  themeConfig: {
    logo: '/logo.svg',

    nav: [
      { text: 'Docs', link: '/getting-started/introduction' },
      {
        text: 'v1.0.0',
        items: [
          { text: 'Changelog', link: 'https://github.com/TestFlowLabs/pest-plugin-bridge/releases' }
        ]
      }
    ],

    sidebar: {
      '/': [
        {
          text: 'Getting Started',
          items: [
            { text: 'Introduction', link: '/getting-started/introduction' },
            { text: 'Installation', link: '/getting-started/installation' },
            { text: 'Quick Start', link: '/getting-started/quick-start' }
          ]
        },
        {
          text: 'Guide',
          items: [
            { text: 'Configuration', link: '/guide/configuration' },
            { text: 'Connection Architecture', link: '/guide/connection' },
            { text: 'Writing Tests', link: '/guide/writing-tests' },
            { text: 'Assertions', link: '/guide/assertions' },
            { text: 'Best Practices', link: '/guide/best-practices' }
          ]
        },
        {
          text: 'CI/CD',
          items: [
            { text: 'Introduction', link: '/ci-cd/introduction' },
            { text: 'Basic Setup', link: '/ci-cd/basic-setup' },
            { text: 'Multi-Repository', link: '/ci-cd/multi-repo' },
            { text: 'Manual Triggers', link: '/ci-cd/manual-trigger' },
            { text: 'SQLite Database', link: '/ci-cd/sqlite' },
            { text: 'MySQL Database', link: '/ci-cd/mysql' },
            { text: 'Caching', link: '/ci-cd/caching' },
            { text: 'Debugging', link: '/ci-cd/debugging' },
            { text: 'Advanced', link: '/ci-cd/advanced' }
          ]
        },
        {
          text: 'API Reference',
          items: [
            { text: 'Configuration', link: '/api/configuration' },
            { text: 'BridgeTrait', link: '/api/bridge-trait' }
          ]
        },
        {
          text: 'Examples',
          items: [
            { text: 'Vue + Vite', link: '/examples/vue-vite' },
            { text: 'Nuxt 3', link: '/examples/nuxt' },
            { text: 'React', link: '/examples/react' },
            { text: 'Authentication Flow', link: '/examples/authentication' }
          ]
        },
        {
          text: 'Playground',
          items: [
            { text: 'Setup', link: '/playground/setup' },
            { text: 'Running Tests', link: '/playground/running-tests' }
          ]
        }
      ]
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/TestFlowLabs/pest-plugin-bridge' }
    ],

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2024-present TestFlowLabs'
    },

    editLink: {
      pattern: 'https://github.com/TestFlowLabs/pest-plugin-bridge/edit/main/docs/:path',
      text: 'Edit this page on GitHub'
    },

    search: {
      provider: 'local'
    }
  }
})
