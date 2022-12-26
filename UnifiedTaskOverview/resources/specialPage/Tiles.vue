<template>
	<div id="unifiedTaskOverview-tiles" class="task-overview">
		<hr>
		<div class="no-tasks" v-if="noTaskDesc">
			<p>{{ $i18n( 'unifiedtaskoverview-label-no-task' ) }}</p>
			<div class="notasks-icon"></div>
		</div>
		<ul class="ul-tiles">
			<transition appear name="list" v-for="item in items" v-bind:key="item.id">
				<single-tile
					v-show="item.isVisible"
					v-bind:type="item.type"
					v-bind:header="item.header"
					v-bind:subheader="item.subheader"
					v-bind:body="item.body"
					v-bind:url="item.url"
				>
				</single-tile>
			</transition>
		</ul>
	</div>
</template>

<script>
var SingleTile = require( './SingleTile.vue' );

	module.exports = {
		name: 'TileView',
		props: {
			items: Array,
			noTaskDesc: String
		},
		components: {
			'single-tile': SingleTile
		}
	};
</script>

<style lang="less">

ul {
	list-style-type: none;
	padding: 0;
}
.ul-tiles {
	margin-top: 2rem;
	margin-left: 0;
	display: flex;
	flex-flow: row wrap;
	position: relative;
}

.list-enter,
.list-leave-to {
	visibility: hidden;
	height: 0;
	margin: 0;
	padding: 0;
	opacity: 0;
}

.list-enter-active,
.list-leave-active {
	transition: all ease-out 250ms;
}
div.no-tasks {
	width: 100;
	text-align: center;
}
.notasks-icon {
	background-image: url('../images/Icon_notasks.svg');
	width: 80px;
	height: 80px;
	background-position: center;
	background-repeat: no-repeat;
	background-size: 80px;
	margin: 1rem auto 0 auto;
}
</style>