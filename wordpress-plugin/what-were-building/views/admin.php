<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap qnl-wrap">
	<h1><?php esc_html_e( "What We're Building", 'what-were-building' ); ?></h1>
	<p class="qnl-lead">
		<?php esc_html_e( 'Connect to the GitHub Pages publication, pick an edition and issue, and WordPress will store an exact copy as a standalone landing page — no theme header, footer, or navigation.', 'what-were-building' ); ?>
	</p>

	<div class="qnl-grid">
		<section class="qnl-card" id="qnl-connect">
			<h2><?php esc_html_e( '1. Connect', 'what-were-building' ); ?></h2>
			<p><?php esc_html_e( 'This should be the public GitHub Pages URL for the newsletter archive.', 'what-were-building' ); ?></p>
			<label class="qnl-label" for="qnl-source-url"><?php esc_html_e( 'GitHub Pages URL', 'what-were-building' ); ?></label>
			<input type="url" class="regular-text qnl-input" id="qnl-source-url" value="<?php echo esc_attr( $source ); ?>" placeholder="https://rickatquorum.github.io/rick-newsletter/">
			<p>
				<button type="button" class="button button-secondary" id="qnl-load-issues">
					<?php esc_html_e( 'Load issues', 'what-were-building' ); ?>
				</button>
			</p>
			<p class="qnl-status" id="qnl-connect-status" hidden></p>
		</section>

		<section class="qnl-card" id="qnl-import-card">
			<h2><?php esc_html_e( '2. Create landing page', 'what-were-building' ); ?></h2>

			<label class="qnl-label" for="qnl-issue"><?php esc_html_e( 'Newsletter', 'what-were-building' ); ?></label>
			<select id="qnl-issue" class="qnl-input" disabled>
				<option value=""><?php esc_html_e( 'Load issues first', 'what-were-building' ); ?></option>
			</select>

			<label class="qnl-label" for="qnl-edition"><?php esc_html_e( 'Edition', 'what-were-building' ); ?></label>
			<select id="qnl-edition" class="qnl-input" disabled>
				<?php foreach ( QNL_Source::EDITIONS as $code => $label ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>"<?php selected( $code, 'QDMS' ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>

			<label class="qnl-label" for="qnl-page-title"><?php esc_html_e( 'WordPress page name', 'what-were-building' ); ?></label>
			<input type="text" class="regular-text qnl-input" id="qnl-page-title" disabled placeholder="<?php esc_attr_e( 'What We\'re Building — August 2026 — DealerMine', 'what-were-building' ); ?>">
			<p class="description"><?php esc_html_e( 'This becomes the WordPress page title and the default URL slug. You can change it later.', 'what-were-building' ); ?></p>

			<p>
				<button type="button" class="button button-primary" id="qnl-import" disabled>
					<?php esc_html_e( 'Create landing page', 'what-were-building' ); ?>
				</button>
			</p>
			<p class="qnl-status" id="qnl-import-status" hidden></p>
			<div class="qnl-result" id="qnl-result" hidden></div>
		</section>
	</div>

	<section class="qnl-card qnl-card-wide">
		<h2><?php esc_html_e( 'Imported landing pages', 'what-were-building' ); ?></h2>
		<?php if ( empty( $pages ) ) : ?>
			<p><?php esc_html_e( 'None yet. Create one above and it will show up here.', 'what-were-building' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Page', 'what-were-building' ); ?></th>
						<th><?php esc_html_e( 'Edition', 'what-were-building' ); ?></th>
						<th><?php esc_html_e( 'Issue', 'what-were-building' ); ?></th>
						<th><?php esc_html_e( 'Created', 'what-were-building' ); ?></th>
						<th><?php esc_html_e( 'Links', 'what-were-building' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $pages as $page ) : ?>
						<?php
						$edition_code = get_post_meta( $page->ID, QNL_Landing::META_EDITION, true );
						$edition_name = isset( QNL_Source::EDITIONS[ $edition_code ] ) ? QNL_Source::EDITIONS[ $edition_code ] : $edition_code;
						$issue_title  = get_post_meta( $page->ID, QNL_Landing::META_TITLE, true );
						$issue_file   = get_post_meta( $page->ID, QNL_Landing::META_ISSUE, true );
						?>
						<tr>
							<td>
								<strong><?php echo esc_html( get_the_title( $page ) ); ?></strong>
							</td>
							<td><?php echo esc_html( $edition_name ); ?></td>
							<td><?php echo esc_html( $issue_title ? $issue_title : $issue_file ); ?></td>
							<td><?php echo esc_html( get_the_date( '', $page ) ); ?></td>
							<td>
								<a href="<?php echo esc_url( get_permalink( $page ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'what-were-building' ); ?></a>
								&nbsp;·&nbsp;
								<a href="<?php echo esc_url( get_edit_post_link( $page ) ); ?>"><?php esc_html_e( 'Edit', 'what-were-building' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</section>
</div>
