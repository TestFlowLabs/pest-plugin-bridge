import { defineConfig } from 'vitepress'

const siteUrl = 'https://bridge.testflowlabs.dev'
const title = 'Pest Bridge Plugin'
const description = 'Test external frontends from Laravel — write browser tests in PHP for Vue, React, Nuxt, Next.js'

export default defineConfig({
  title,
  description,
  lang: 'en-US',

  // Sitemap generation
  sitemap: {
    hostname: siteUrl,
  },

  // Clean URLs (no .html extension)
  cleanUrls: true,

  // Last updated timestamp
  lastUpdated: true,

  markdown: {
    theme: {
      light: 'github-light',
      dark: 'github-dark'
    },
    lineNumbers: false
  },

  head: [
    // Favicon
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/logo.svg' }],
    ['link', { rel: 'icon', type: 'image/png', sizes: '32x32', href: '/favicon-32x32.png' }],
    ['link', { rel: 'icon', type: 'image/png', sizes: '16x16', href: '/favicon-16x16.png' }],
    ['link', { rel: 'apple-touch-icon', sizes: '180x180', href: '/apple-touch-icon.png' }],

    // Open Graph
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:site_name', content: title }],
    ['meta', { property: 'og:title', content: title }],
    ['meta', { property: 'og:description', content: description }],
    ['meta', { property: 'og:image', content: `${siteUrl}/og-image.png` }],
    ['meta', { property: 'og:image:width', content: '1200' }],
    ['meta', { property: 'og:image:height', content: '630' }],
    ['meta', { property: 'og:url', content: siteUrl }],
    ['meta', { property: 'og:locale', content: 'en_US' }],

    // Twitter Card
    ['meta', { name: 'twitter:card', content: 'summary_large_image' }],
    ['meta', { name: 'twitter:title', content: title }],
    ['meta', { name: 'twitter:description', content: description }],
    ['meta', { name: 'twitter:image', content: `${siteUrl}/og-image.png` }],

    // Additional SEO
    ['meta', { name: 'author', content: 'TestFlowLabs' }],
    ['meta', { name: 'keywords', content: 'pest, php, testing, browser testing, vue, react, nuxt, nextjs, laravel, playwright' }],
    ['meta', { name: 'robots', content: 'index, follow' }],
    ['meta', { name: 'theme-color', content: '#ec5d42' }],

    // Canonical (base, will be extended per-page)
    ['link', { rel: 'canonical', href: siteUrl }],
  ],

  // Dynamic meta tags per page
  transformPageData(pageData) {
    const canonicalUrl = `${siteUrl}/${pageData.relativePath}`
      .replace(/index\.md$/, '')
      .replace(/\.md$/, '')

    pageData.frontmatter.head ??= []
    pageData.frontmatter.head.push(
      ['link', { rel: 'canonical', href: canonicalUrl }],
      ['meta', { property: 'og:url', content: canonicalUrl }]
    )

    // Use page title if available
    if (pageData.title) {
      const pageTitle = `${pageData.title} | ${title}`
      pageData.frontmatter.head.push(
        ['meta', { property: 'og:title', content: pageTitle }],
        ['meta', { name: 'twitter:title', content: pageTitle }]
      )
    }

    // Use page description if available
    if (pageData.description) {
      pageData.frontmatter.head.push(
        ['meta', { property: 'og:description', content: pageData.description }],
        ['meta', { name: 'twitter:description', content: pageData.description }]
      )
    }
  },

  themeConfig: {
    logo: '/logo.svg',
    siteTitle: 'Pest Bridge Plugin',

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
            { text: 'Best Practices', link: '/guide/best-practices' },
            { text: 'Performance', link: '/guide/performance' },
            { text: 'Troubleshooting', link: '/guide/troubleshooting' },
            { text: 'Limitations', link: '/guide/limitations' }
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
          text: 'Examples',
          items: [
            { text: 'Vue + Vite', link: '/examples/vue-vite' },
            { text: 'Nuxt 3', link: '/examples/nuxt' },
            { text: 'React', link: '/examples/react' },
            { text: 'Authentication Flow', link: '/examples/authentication' }
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
