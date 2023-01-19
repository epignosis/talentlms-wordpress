<?php
/**
 * @package talentlms-wordpress
 */

namespace TalentlmsIntegration;
use TalentlmsIntegration\Services\PluginService;
use WP_Widget;

class Widget extends WP_Widget implements PluginService{
	public string $widget_ID;

	public string $version;

	public string $widget_name;

	public array $widget_options = array();

	function __construct() {

		$this->widget_ID = 'tlms_widget';
		$this->version = '1.0.0';
		$this->widget_name = 'TalentLMS Widget';

		$this->widget_options = array(
			'classname' => $this->widget_ID,
			'description' => $this->widget_name,
			'customize_selective_refresh' => true,
		);
	}

	public function register()
	{
		parent::__construct( $this->widget_ID, $this->widget_name, $this->widget_options );

		add_action( 'widgets_init', array( $this, 'widgetsInit' ) );

		$this->enqueue_widget_assets();
	}

	public function widgetsInit()
	{
		register_widget( $this );
	}

	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}
		$courses = tlms_selectCourses(); ?>
		<div class="tlms-widget-container">
			<?php foreach ($courses as $course): ?>
				<div class="tlms-widget-item">
					<a href="<?php echo get_site_url(); ?>/courses/?tlms-course=<?php echo $course->id; ?>"><img src="<?php echo $course->big_avatar; ?>" alt="<?php echo $course->name; ?>" /><?php echo $course->name; echo ($course->course_code) ? "(".$course->course_code.")":''; ?></a>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Our Courses', 'talentlms' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'talentlms' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );

		return $instance;
	}

	public function enqueue_widget_assets(){
		wp_enqueue_style( 'tlms-widget',  TLMS_BASEURL . '/assets/css/talentlms-widget.css', '', $this->version );
	}
}
