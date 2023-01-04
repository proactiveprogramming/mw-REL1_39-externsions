---
aside: false
isHomepage: true
---
<script setup>
import { CdxIcon, CdxButton, CdxCard } from '@wikimedia/codex';
import { cdxIconLogoWikimedia, cdxIconKey, cdxIconPuzzle, cdxIconInfoFilled } from '@wikimedia/codex-icons';
import { version } from '../../codex/package.json';
import CdxDocsVersionBanner from '../src/components/version-banner/VersionBanner.vue';
</script>

<div class="cdx-docs-home">

<div class="cdx-docs-home__hero">
	<cdx-icon class="cdx-docs-home__hero__icon" :icon="cdxIconLogoWikimedia" />
	<h1 class="cdx-docs-home__hero__title">Codex</h1>
	<p class="cdx-docs-home__hero__tagline">Wikimedia Design System Toolkit</p>
</div>

<cdx-docs-version-banner />

**Codex is a toolkit for building user interfaces within the Wikimedia Design System.**<br>
Current version: **{{ version }}**

Codex is maintained by the [Design Systems Team](https://www.mediawiki.org/wiki/Design_Systems_Team)
of the [Wikimedia Foundation](https://wikimediafoundation.org/).

<div class="cdx-docs-home__resources">
	<cdx-card url="./using-codex/about.html">
		<template #title>Using Codex</template>
		<template #description>Learn how to use Codex to design and build user interfaces</template>
	</cdx-card>
	<cdx-card url="./contributing/overview.html">
		<template #title>Contributing guidelines</template>
		<template #description>Learn about how we work on Codex and how you can help</template>
	</cdx-card>
</div>

## Features

<div class="cdx-docs-home__features">
	<cdx-card url="./design-tokens/overview.html" :icon="cdxIconKey">
		<template #title>Design tokens</template>
		<template #description>Write styles consistent with the Wikimedia Design System</template>
	</cdx-card>
	<cdx-card url="./components/overview.html" :icon="cdxIconPuzzle">
		<template #title>Components</template>
		<template #description>Build usable, accessible, translatable applications</template>
	</cdx-card>
	<cdx-card url="./icons/overview.html" :icon="cdxIconInfoFilled">
		<template #title>Icons</template>
		<template #description>Access a collection of icons with language and directionality variants</template>
	</cdx-card>
</div>

## Resources

<div class="cdx-docs-home__resources">
	<cdx-card url="https://www.figma.com/file/KoDuJMadWBXtsOtzGS4134/%E2%9D%96-Codex-components?node-id=1891%3A4420">
		<template #title>Figma kit</template>
		<template #description>View the Figma design specifications</template>
	</cdx-card>
	<cdx-card url="https://www.mediawiki.org/wiki/Codex">
		<template #title>Using Codex in MediaWiki</template>
		<template #description>Learn about using Codex within the MediaWiki platform</template>
	</cdx-card>
	<cdx-card url="https://gerrit.wikimedia.org/r/admin/repos/design/codex">
		<template #title>View code on Gerrit</template>
		<template #description>The canonical Codex codebase</template>
	</cdx-card>
	<cdx-card url="https://github.com/wikimedia/design-codex">
		<template #title>View code on GitHub</template>
		<template #description>A mirror of the Gerrit codebase on GitHub</template>
	</cdx-card>
</div>

</div>

<style lang="less">
@import ( reference ) '@wikimedia/codex-design-tokens/theme-wikimedia-ui.less';

/* stylelint-disable selector-class-pattern */
.cdx-docs-home {
	.cdx-card {
		color: @color-base;

		&:hover {
			color: @color-base;
			text-decoration: @text-decoration-none;
		}
	}

	&__hero {
		background-color: @background-color-progressive;
		color: @color-inverted;
		// Equals 100% + ( 2 * negative margin per side ).
		width: calc( @size-full + ( @spacing-200 * 2 ) );
		margin-right: -@spacing-200;
		margin-bottom: @spacing-200;
		margin-left: -@spacing-200;
		padding: @spacing-200;

		&__icon {
			color: @color-inverted;
			margin-bottom: @spacing-400;

			svg {
				// 5em equivalent to match the title.
				min-width: 80px;
				min-height: 80px;
				width: 5em;
				height: 5em;
			}
		}

		// Need to put `.vp-doc` here to override the default VitePress heading styles.
		.vp-doc &__title {
			font-family: @font-family-sans;
			font-size: 5em;
			font-weight: @font-weight-bold;
			line-height: initial;
		}

		.vp-doc &__tagline {
			margin: 0 0 0 @spacing-50;
			font-family: @font-family-sans;
			font-size: 1.5em;
		}
	}

	&__features {
		display: flex;
		flex-direction: column;
		row-gap: @spacing-50;
	}

	&__resources {
		display: flex;
		flex-wrap: wrap;
		/* stylelint-disable-next-line plugin/no-unsupported-browser-features */
		column-gap: @spacing-50;
		row-gap: @spacing-50;
		margin-top: @spacing-200;

		/* stylelint-disable-next-line no-descending-specificity */
		.cdx-card {
			width: calc( @size-half - @spacing-25 );

			&:hover {
				text-decoration: @text-decoration-none;
			}
		}
	}
}
/* stylelint-enable selector-class-pattern */
</style>
