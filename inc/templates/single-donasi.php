<?php
/**
 * The template for displaying all single donasi
 *
 * @package justg
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_header();

$container = get_theme_mod( 'justg_container_type', 'container' );
?>

<div class="wrapper py-5 my-5" id="single-wrapper">

	<div class="<?php echo esc_attr( $container ); ?>" id="content" tabindex="-1">

		<div class="row">

			<div class="col-md">

				<main class="site-main col order-2 px-md-0" id="main">

					<?php
					
					while ( have_posts() ) {
						the_post();
                        $date = get_the_modified_date( 'd-m-Y', get_the_ID() );
                        ?>
                        <article <?php post_class(); ?> id="post-<?php the_ID(); ?>">

                            <div class="block-primary mb-4">            
                                <div class="row">
                                    <div class="col-md-6 col-xl-5">
                                        <?php if ( has_post_thumbnail() ) : ?>
                                            <?php the_post_thumbnail( 'full', array( 'class' => 'w-100' ) ); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md">
                                        <h1 class="h2"><?php echo get_the_title(); ?></h1>
                                        <div class="mb-2">
                                            <small>
                                                Kategori: <?php echo do_shortcode( '[wpbb post:terms_list taxonomy="kategori-donasi" separator=", " linked="yes"]' ); ?>
                                                | Dilihat: <?php echo do_shortcode( '[statistik_kunjungan stat="post"]' ); ?>
                                                | Sejak: <?php echo $date; ?>
                                            </small>
                                        </div>
                                        <div class="single-progress mb-3">
                                            <?php echo do_shortcode( '[velocity-progress-donasi]' ); ?>
                                        </div>
                                        <div class="mb-3">
                                            <?php echo do_shortcode( '[velocity-donasi-button]' ); ?>
                                        </div>
                                        <div class="mb-3">
                                            <p class="mb-1">Bagikan donasi:</p>
                                            <?php echo do_shortcode( '[velocity-donasi-share]' ); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card p-3 border">
                                <ul class="nav nav-tabs" id="myTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="fs-6 nav-link fw-bold active" id="detail-tab" data-bs-toggle="tab" data-bs-target="#detail" type="button" role="tab" aria-controls="detail" aria-selected="true">Detail Donasi</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="fs-6 nav-link fw-bold" id="kabar-tab" data-bs-toggle="tab" data-bs-target="#kabar" type="button" role="tab" aria-controls="kabar" aria-selected="false">Kabar Terbaru</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="fs-6 nav-link fw-bold" id="donatur-tab" data-bs-toggle="tab" data-bs-target="#donatur" type="button" role="tab" aria-controls="donatur" aria-selected="false">Daftar Donatur</button>
                                    </li>
                                </ul>
                                <div class="tab-content" id="myTabContent">
                                    <div class="tab-pane fade show active" id="detail" role="tabpanel" aria-labelledby="detail-tab">
                                        <div class="pt-3"><?php echo get_the_content(); ?></div>
                                    </div>
                                    <div class="tab-pane fade" id="kabar" role="tabpanel" aria-labelledby="kabar-tab">
                                        <div class="pt-3">
                                          <?php echo do_shortcode( '[velocity-update-donasi]' ); ?>                  
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="donatur" role="tabpanel" aria-labelledby="donatur-tab">
                                        <div class="pt-3"><?php echo do_shortcode( '[velocity-daftar-donatur]' ); ?></div>
                                    </div>
                                </div>
                            </div>

                        </article><!-- #post-## -->

                        <?php
						// START related donasi
							$idp = get_the_ID();
							$argsrelated = [
								'post_type'      => 'donasi',
								'post__not_in'   => [ $idp ],
								'posts_per_page' => 4,
							];

							$cats = wp_get_post_terms( get_the_ID(), 'kategori-donasi' );
							$cats_ids = array();
							foreach ( $cats as $wpex_related_cat ) {
								$cats_ids[] = $wpex_related_cat->term_id;
							}

							if ( ! empty( $cats_ids ) ) {
								$argsrelated['tax_query'] = [
									[
										'taxonomy' => 'kategori-donasi',
										'field'    => 'term_id',
										'terms'    => $cats_ids,
									]
								];
							}

							$related_query = new wp_query( $argsrelated );
							// The Loop
							if ( $related_query->have_posts() ) :
								echo '<div class="related-post-product block-primary mt-5">';
									echo '<div class="title-single-part fs-4 fw-bold mb-2">Donasi Terkait</div>';
									echo '<div class="row">';
									while ( $related_query->have_posts() ) : $related_query->the_post();
                                        echo '<div class="col-md-4 mb-4">';
                                            echo '<div class="border h-100">';
                                                echo do_shortcode( '[velocity-donasi-loop]' );
                                            echo '</div>';
                                        echo '</div>';
									endwhile;
									echo '</div>';
								echo '</div>';
							endif;

							wp_reset_postdata();
						// END related donasi

						// If comments are open or we have at least one comment, load up the comment template.
						if ( comments_open() || get_comments_number() ) :

							do_action( 'justg_before_comments' );
							comments_template();
							do_action( 'justg_after_comments' );

						endif;
					}
					?>

				</main><!-- #main -->

			</div>

		</div><!-- .row -->

	</div><!-- #content -->

</div><!-- #single-wrapper -->

<?php
get_footer();
