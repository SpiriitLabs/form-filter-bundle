import { defineConfig } from 'vitepress'
import { execSync } from 'node:child_process'

function bundleVersion(): string {
  try {
    return execSync('git describe --tags --abbrev=0', { encoding: 'utf8' }).trim()
  } catch {
    return 'Links'
  }
}

const BASE = '/form-filter-bundle/'
const SITE_URL = `https://spiriitlabs.github.io${BASE}`

function absoluteUrl(relativePath: string): string {
  return SITE_URL + relativePath.replace(/(?:index)?\.md$/, '')
}

export default defineConfig({
  title: 'Form Filter Bundle',
  description: 'Symfony bundle for dynamic filtering, search forms and Doctrine query generation',
  lang: 'en-US',
  base: BASE,
  cleanUrls: true,
  lastUpdated: true,
  sitemap: {
    hostname: SITE_URL,
  },
  head: [
    ['link', { rel: 'icon', href: `${BASE}favicon.svg`, type: 'image/svg+xml' }],
  ],
  transformPageData(pageData, { siteConfig }) {
    const { title: siteTitle, description: siteDescription } = siteConfig.site
    const isHome = pageData.frontmatter.layout === 'home'
    const title = isHome ? siteTitle : `${pageData.title} | ${siteTitle}`
    const description = pageData.frontmatter.description ?? siteDescription
    const url = absoluteUrl(pageData.relativePath)

    pageData.frontmatter.head ??= []
    pageData.frontmatter.head.push(
      ['link', { rel: 'canonical', href: url }],
      ['meta', { property: 'og:type', content: 'website' }],
      ['meta', { property: 'og:site_name', content: siteTitle }],
      ['meta', { property: 'og:title', content: title }],
      ['meta', { property: 'og:description', content: description }],
      ['meta', { property: 'og:url', content: url }],
    )

    if (isHome) {
      pageData.frontmatter.head.push(
        ['script', { type: 'application/ld+json' }, JSON.stringify({
          '@context': 'https://schema.org',
          '@type': 'SoftwareSourceCode',
          name: siteTitle,
          description: siteDescription,
          codeRepository: 'https://github.com/SpiriitLabs/form-filter-bundle',
          programmingLanguage: 'PHP',
          runtimePlatform: 'Symfony',
          license: 'https://opensource.org/licenses/MIT',
          url: SITE_URL,
          author: { '@type': 'Organization', name: 'Spiriit', url: 'https://www.spiriit.com' },
        })],
      )
    }
  },
  themeConfig: {
    logo: { src: '/logo.svg', alt: 'Form Filter Bundle' },
    nav: [
      { text: 'Guide', link: '/guide/installation' },
      { text: 'Features', link: '/features/provided-types' },
      { text: 'Advanced', link: '/advanced/working-with-other-bundles' },
      {
        text: bundleVersion(),
        items: [
          { text: 'Packagist', link: 'https://packagist.org/packages/spiriitlabs/form-filter-bundle' },
          { text: 'Changelog', link: 'https://github.com/SpiriitLabs/form-filter-bundle/releases' },
        ],
      },
    ],
    sidebar: [
      {
        text: 'Getting Started',
        items: [
          { text: 'Installation', link: '/guide/installation' },
          { text: 'Configuration', link: '/guide/configuration' },
          { text: 'Basics & inner workings', link: '/guide/basics' },
        ],
      },
      {
        text: 'Features',
        items: [
          { text: 'Provided filter types', link: '/features/provided-types' },
          { text: 'Working with the bundle', link: '/features/working-with-the-bundle' },
          { text: 'The FilterTypeExtension', link: '/features/filtertypeextension' },
          { text: 'Remembering and sharing filters', link: '/features/persistence' },
          { text: 'Debugging filters', link: '/features/debugging' },
        ],
      },
      {
        text: 'Advanced',
        items: [
          { text: 'Working with other bundles', link: '/advanced/working-with-other-bundles' },
          { text: 'Advanced usage with PagerFanta', link: '/advanced/pagerfanta' },
        ],
      },
    ],
    socialLinks: [
      { icon: 'github', link: 'https://github.com/SpiriitLabs/form-filter-bundle' },
    ],
    editLink: {
      pattern: 'https://github.com/SpiriitLabs/form-filter-bundle/edit/main/docs/:path',
      text: 'Edit this page on GitHub',
    },
    search: {
      provider: 'local',
    },
    footer: {
      message: 'Built and maintained by <a href="https://www.spiriit.com" target="_blank" rel="noreferrer">Spiriit</a> — released under the MIT License.',
      copyright: 'Copyright © Spiriit',
    },
  },
})
