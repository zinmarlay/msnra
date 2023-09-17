<?php
/**
 * XO Slider widget.
 *
 * @package xo-slider
 */

/**
 * XO Slider widget class.
 */
class XO_Slider_Widget extends WP_Widget {
	/**
	 * Construction.
	 */
	public function __construct() {
		parent::__construct(
			'liteslider',
			__( 'Slider (XO Slider)', 'xo-liteslider' ),
			array(
				'classname'   => 'widget_liteslider',
				'description' => __( 'Display Slider', 'xo-liteslider' ),
			)
		);
	}

	/**
	 * Echoes the widget content.
	 *
	 * @param array $args     See WP_Widget::widget().
	 * @param array $instance See WP_Widget::widget().
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget']; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title']; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		}
		if ( isset( $instance['slider_id'] ) ) {
			$slider_id = $instance['slider_id'];
			echo do_shortcode( "[xo_slider id={$slider_id}]" );
		}
		echo $args['after_widget']; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Outputs the settings update form.
	 *
	 * @param array $instance See WP_Widget::form().
	 */
	public function form( $instance ) {
		$title     = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$slider_id = isset( $instance['slider_id'] ) ? intval( $instance['slider_id'] ) : 0;

		echo '<p>';
		echo '<label for="' . esc_attr( $this->get_field_id( 'title' ) ) . '">' . esc_html( __( 'Title:', 'xo-liteslider' ) ) . '</label>';
		echo '<input class="widefat" id="' . esc_attr( $this->get_field_id( 'title' ) ) . '" name="' . esc_attr( $this->get_field_name( 'title' ) ) . '" type="text" value="' . esc_attr( $title ) . '" />';
		echo '</p>';

		$posts = get_posts(
			array(
				'post_type'      => 'xo_liteslider',
				'post_status'    => 'publish',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'posts_per_page' => -1,
			)
		);
		foreach ( $posts as $post ) {
			$sliders[] = array(
				'id'     => $post->ID,
				'title'  => $post->post_title,
				'active' => ( $slider_id === $post->ID ? true : false ),
			);
		}
		echo '<p>';
		echo '<label for="' . esc_attr( $this->get_field_id( 'slider_id' ) ) . '">' . esc_html( __( 'Slider:', 'xo-liteslider' ) ) . '</label>';
		echo '<select id="' . esc_attr( $this->get_field_id( 'slider_id' ) ) . '" name="' . esc_attr( $this->get_field_name( 'slider_id' ) ) . '" class="widefat">';
		foreach ( $sliders as $slider ) {
			echo '<option value="' . esc_attr( $slider['id'] ) . '"' . ( $slider['active'] ? ' selected=selected' : '' ) . '>' . esc_html( $slider['title'] ) . '</option>';
		}
		echo '</select>';
		echo '</p>' . "\n";
	}

	/**
	 * Updates a particular instance of a widget.
	 *
	 * @param array $new_instance See WP_Widget::update().
	 * @param array $old_instance See WP_Widget::update().
	 * @return array See WP_Widget::update().
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']     = wp_strip_tags( $new_instance['title'] );
		$instance['slider_id'] = wp_strip_tags( $new_instance['slider_id'] );

		return $instance;
	}
}
