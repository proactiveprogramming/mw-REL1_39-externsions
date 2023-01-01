<div class="page-header__contribution-buttons">
	<?php if ( $actionButton->shouldDisplay() ): ?>
		<?= $app->renderView( 'Wikia\PageHeader\PageHeader', 'actionButton', [
			'actionButton' => $actionButton
		] ); ?>
	<?php endif; ?>
	<?php foreach ( $buttons->buttons as $button ): ?>
		<a class="wds-button <?= $button->class ?>" href="<?= $button->href ?>"
		   id="<?= $button->id ?>">
			<?php if ( !empty( $button->icon ) ): ?>
				<?= DesignSystemHelper::renderSvg( $button->icon, 'wds-icon wds-icon-small' ); ?>
			<?php endif; ?>
			<span><?= $button->label ?></span>
		</a>
	<?php endforeach; ?>
</div>
