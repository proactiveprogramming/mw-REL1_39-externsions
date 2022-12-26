<template>
	<div>
		<div v-i18n-html:vuetest-app-see-also class="mw-vuetest__see-also"></div>

		<!-- i18n messages can be used like this:-->
		<h2>{{ $i18n( 'vuetest-app-welcome' ) }}</h2>
		<p v-i18n-html:vuetest-app-intro></p>

		<p>Counter value: {{ counter }}</p>
		<cdx-button
			action="destructive"
			:disabled="!canDecrease"
			@click="decrease"
		>
			Decrease
		</cdx-button>
		<cdx-button
			action="progressive"
			type="primary"
			@click="increase"
		>
			<cdx-icon :icon="cdxIconAdd"></cdx-icon>
			Increase
		</cdx-button>
	</div>
</template>

<script>
// Codex is available from ResourceLoader at runtime
// and is available without needing a build step.
const {
	CdxButton,
	CdxIcon
} = require( '@wikimedia/codex' );

// Codex Icons are also available from ResourceLoader.
// To avoid loading all of them, you will probably want
// to use a RL callback so that only the specified icons
// are delivered; see this project's extension.json file
// for an example.
const { cdxIconAdd } = require( './icons.json' );

// @vue/component
module.exports = exports = {
	name: 'App',
	components: {
		CdxButton,
		CdxIcon
	},
	data() {
		return {
			counter: 0,
			cdxIconAdd
		};
	},
	computed: {
		canDecrease() {
			return this.counter > 0;
		}
	},
	methods: {
		decrease() {
			this.counter--;
		},
		increase() {
			this.counter++;
		}
	}
};

</script>

<style lang="less">
.mw-vuetest {
	&__see-also {
		padding-left: 1em;
		font-style: italic;
	}
}
</style>
