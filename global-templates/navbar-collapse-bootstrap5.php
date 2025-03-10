<?php
/**
 * Header Navbar (bootstrap5)
 *
 * @package Understrap
 * @since 1.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$container = get_theme_mod( 'understrap_container_type' );
?>

<nav id="main-nav" class="navbar navbar-expand-md navbar-dark bg-primary" aria-labelledby="main-nav-label">

	<h2 id="main-nav-label" class="screen-reader-text">
		<?php esc_html_e( 'Main Navigation', 'understrap' ); ?>
	</h2>

	<div class="<?php echo esc_attr( $container ); ?>">
	
	    <div class="d-flex align-items-center">
            <!-- Your site branding in the menu -->
		    <?php get_template_part( 'global-templates/navbar-branding' ); ?>
	    </div>

	    <button
	        class="navbar-toggler d-none"
	        type="button"
	        data-bs-toggle="collapse"
	        data-bs-target="#navbarNavDropdown"
	        aria-controls="navbarNavDropdown"
	        aria-expanded="false"
	        aria-label="<?php esc_attr_e( 'Toggle navigation', 'understrap' ); ?>"
	    >
	        <span class="navbar-toggler-icon d-none"></span>
	    </button>

	    <!-- The WordPress Menu goes here -->
	    <?php if ( is_user_logged_in() ) : ?>
            <div class="welcome-message">
		        <?php
		        $current_user = wp_get_current_user();
		        echo '<p class="welcome-text">Hi ' . esc_html( $current_user->user_firstname ) . ', Welcome to the Optimise Learning Hub!</p>';
				echo '<a id="logout-link" href="'. wp_logout_url( home_url() ) .'"><i class="fas fa-sign-out-alt"></i></a>'; // Added logout link
		        ?>
		    </div>
        <?php else :
	    wp_nav_menu(
	        array(
	            'theme_location'  => 'primary',
	            'container_class' => 'collapse navbar-collapse',
	            'container_id'    => 'navbarNavDropdown',
	            'menu_class'      => 'navbar-nav ms-auto',
	            'fallback_cb'     => '',
	            'menu_id'         => 'main-menu',
	            'depth'           => 2,
	            'walker'          => new Understrap_WP_Bootstrap_Navwalker(),
	        )
	    );
        endif;
	    ?>

	</div><!-- .container(-fluid) -->

</nav><!-- #main-nav -->
