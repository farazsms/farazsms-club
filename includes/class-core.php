<?php
/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

class FARAZSMS_CLUB extends FARAZSMS_CLUB_BASE {
	private static ?FARAZSMS_CLUB $_instance = null;

	public function __construct() {
		$this->include_all();
		add_action('activate_plugin',[$this,'activate_plugin']);
		add_action( 'init', array( $this, 'add_languages_dir' ) );
		add_action( 'get_footer', array( $this, 'load_css_farsi_font' ) );
		add_action( 'wp_before_admin_bar_render', array( $this, 'wpb_custom_logo' ) );
		add_action( 'edit_user_profile', [ $this, 'after_register' ] );
		add_action( 'edit_user_profile_update', [ $this, 'after_register' ] );
		add_action( 'activate_plugin', [ $this, 'default_options' ] );
		add_action( 'admin_menu', [ $this, 'settings' ] );
		add_filter('update_user_metadata',[$this,'updated_user_meta'],10,4);
		add_action('woocommerce_thankyou',[$this,'woo_payment_finished']);
		try {
            add_action("woocommerce_thankyou",function ($post_id){
                $get_post_meta = get_post_meta($post_id);

            });
 }
    catch (Exception $e){echo $e;}
    if (get_transient('farazsms-club-admin_notice')){
        add_action('admin_notices',[$this,'admin_notices_activated']);
    }
//		add_filter( 'wp_nav_menu_items', array( $this, 'user_nav_menu' ), 10, 2 );
//		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_select2_vue' ] );
	}

	private function include_all() {
		if ( ! class_exists( "FARAZSMS_CLUB_CONFIG" ) ) {
			require_once( 'class-config.php' );
		}
	}

        static function activate_plugin(){
//	    add_action( 'activate_plugin', [ $this, 'activate_plugin' ] );
        set_transient('farazsms-club-admin_notice',true,10);
                FARAZSMS_CLUB_CONFIG::createdb();

    }

	static function add_languages_dir() {
		load_plugin_textdomain( 'farazsms-club',
			false,
			basename( dirname( FARAZSMS_CLUB_INDEX_FILE ) ) . '/languages' );
	}

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new FARAZSMS_CLUB();
		}

		return self::$_instance;
	}

	public static function get_options() {
		$option = get_option( 'farazsms_club_forms_options' );
		if ( ! $option ) {
			add_option( 'farazsms_club_forms_options', self::$default_option );
			$option = get_option( 'farazsms_club_forms_options' );
		}

		return $option;
	}

    static function admin_notices_activated(){
	     $suggest=__( '<h2> Special Offer </h2>
                 By registering in the sales cooperation system and introducing the Faraz SMS SMS system to your friends, 30% cooperation fee in
                 Get sales.
                 <a href="https://farazsms.com/affiliate" target="_blank" rel="noopener"> More info and start earning money
                     Internet </a>', 'farazsms-club' );

	     ?>  <div class='notice notice-success is-dismissible'>
                    <p> <?php echo $suggest;?> </p>
                    </div>
                    <?php
    }

	function load_css_farsi_font() {
		wp_enqueue_style( 'kala_fonts', FARAZSMS_CLUB_URL . 'assets/styles/fonts.css' );
		wp_enqueue_style( 'kala_nahid_sahel_font', FARAZSMS_CLUB_URL . 'assets/styles/nahid-sahel-font.css',
			array( 'kala_fonts' ) );
		wp_enqueue_style( 'kala_styles', FARAZSMS_CLUB_URL . 'assets/styles/styles.css',
			array( 'kala_fonts' ) );

		wp_register_style( 'kala_gform', FARAZSMS_CLUB_URL . 'assets/styles/gravity-forms.css',
			array( 'kala_fonts' ) );


	}

	function user_nav_menu( $nav, $args ) {
//	    if( $args->theme_location == 'primary' )
		/** @var LP_Order $learn_press_get_orders */
//	    $learn_press_get_orders = learn_press_get_order(286);

//	    $learn_press_get_orders.get_items();


//	    $LP_order = new LP_Order();
//	    $LP_order->set_user_id(3);
//	    try {
//		    $LP_order->save();
//	    } catch ( Exception $e ) {
//		    echo 1;
//	    }
//	    $add_item = $LP_order->add_item( 11 );
//	    $LP_order->get_items();
//	    $LP_order->set_status('completed');

		if ( is_user_logged_in() ) {
			$page       = get_option( 'learn_press_courses_page_id' );
			$post       = get_post( $page );
			$post_title = $post->post_title;
			$link       = get_post_permalink( $page );


			$nav = $nav . "<li class='menu-header-search'><a href='$link'>$post_title</a> </li>";

		}
		$profile_page            = get_option( 'learn_press_profile_page_id' );
		$profile_page_post       = get_post( $profile_page );
		$profile_page_post_title = $profile_page_post->post_title;

		return $nav . "
			 <li class='menu-header-search'><a href='/?p=$profile_page'>$profile_page_post_title</a> </li>
			 ";
//	    learn_press_create_order();

	}

	function wpb_custom_logo() {
		?>
        <style type="text/css">
            #wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon:before {
                background-image: url(' .<?php echo FARAZSMS_CLUB_URL;?> . ' assets/images/hat-small-white.png) !important;
                background-position: 0 0;
                color: rgba(0, 0, 0, 0);
            }

            #wpadminbar #wp-admin-bar-wp-logo.hover > .ab-item .ab-icon {
                background-position: 0 0;
            }
        </style>
		<?php
	}

	function after_register( $user_id ) {
		$user  = get_user_meta( $user_id );
		$phone = $user['digits_phone'];
		if ( $phone == null || strlen( $phone[0] ) < 10 ) {
			return;
		}
//    $phone = '0'.substr($phone,-10);
		$options  = self::default_options();
		$body     = array(
			"uname"       => $options['uname'],
			"pass"        => $options['pass'],
			"phoneBookId" => $options['phoneBookId'],
			"op"          => "phoneBookAdd",
			"number"      => $phone[0]
		);
		$response = wp_remote_post( $options['url'], array(
				'method'      => 'POST',
				'headers'     => [
					'Content-Type' => 'application/json',
				],
				'data_format' => 'body',
				'body'        => json_encode( $body )
			)
		);


	}

	function default_options($force=false) {
		return FARAZSMS_CLUB_CONFIG::options($force);
	}

	function settings() {
		add_options_page( __( 'FarazSMS Options', 'farazsms-club' ),  __( 'FarazSMS Options', 'farazsms-club' ), 'manage_options',
			'farazsms-club', [ $this, 'settings_html' ] );
	}

    function settings_html() {
	    $this->enqueue_select2_vue();
	    $options = $this->default_options();
		$configs = FARAZSMS_CLUB_CONFIG::getInstance();
		$plugins = array();
	    $tab=$_GET['tab'];
	    $help = '';
	    $phonebook = '';
	    $account = '';
	    if (strtolower($tab)=='phonebook'){$phonebook='class="is-active"';}
	    else if (strtolower($tab)=='account'){$account='class="is-active"';}
	    else {$help='class="is-active"';}
		if ( strtoupper( $_SERVER['REQUEST_METHOD'] ) == 'POST' ) {
			$options     = FARAZSMS_CLUB_CONFIG::options();
			if(strtolower($tab)==='phonebook'){
			foreach ($_POST as $item=>$value){
			    $options[$item]=[];
			    foreach ($value as $k){
			          array_push($options[$item],intval($k));
			    }
			}
			}
			elseif (strtolower($tab)==='account') {
			    $options['uname']= sanitize_text_field($_POST['uname']);
			    $options['pass']=sanitize_text_field($_POST['pass']);
			}
			update_option( 'farazsms_options', $options );
			$options = $this->default_options(true);
			$successful = __('successfully saved','farazsms-club');
			echo "<h3 class='is-success'>$successful</h3>";
		}
		if((strlen($options['uname'])<1 || strlen($options['pass'])<1) && strlen($account) < 1){
	        wp_redirect("?page=farazsms-club&tab=account");
	    }
		?>
		<div style="direction: rtl;margin-right: auto;margin-left: 0">
            <div class="tabs is-boxed is-centered is-medium" style="direction: rtl">
                <ul>
                     <li <?php echo $help;?> >
                             <a href="?page=farazsms-club&tab=help">
                    <span class="icon"><i class="dashicons-before dashicons-smiley" aria-hidden="true"></i></span>
                    <span><?php echo __('help','farazsms-club');?></span>
                  </a>
                     </li>
                     <li <?php echo $phonebook;?>>
                            <a href="?page=farazsms-club&tab=phonebook">
                    <span class="icon"><i class="dashicons-before dashicons-book" aria-hidden="true"></i></span>
                    <span><?php echo __('Phonebook','farazsms-club');?></span>
                  </a>
                     </li>
                     <li <?php echo $account;?>>
                         <a href="?page=farazsms-club&tab=account">
                    <span class="icon"><i class="dashicons-before dashicons-unlock" aria-hidden="true"></i></span>
                    <span><?php echo __('Username and password','farazsms-club');?></span>
                  </a>
                     </li>
                </ul>
            </div>
                <?php if(strlen($help)> 0) {?>
                <div>

                <h1>راهنما استفاده از باشگاه مشتریان</h1>
                <br>
                ابتدا وارد حساب کاربری خود در سامانه پیامکی فراز اس ام اس شوید، از منو سمت راست > دفترچه تلفن > گروه
                دفترچه تلفن یک دفترچه جدید ایجاد نمایید، بعد از ایجاد دفترچه تلفن سامانه برای هر دفترچه تلفن یک کد منحصر
                به فرد می دهد همراه با نام کاربری ، رمز و کد دفترچه تلفن در این صفحه قرار داده و تنظیمات را ذخیره کنید
                از این پس شماره موبایل مشتریان ووکامرس و دیجیتس به صورت خودکار در دفترچه تلفن سامانه پیامکی فراز اس ام
                اس شما ذخیره می شود. در صورتی که هنوز سامانه پیامک را خریداری نکرده اید از کد تخفیف saeb استفاده نمایید.
                <br>
                <br>
                </div>
<?php } ?>
                <?php if(strlen($phonebook)> 0) {?>

        <form  method="post" style="direction: rtl">
            <table class="form-table">
                <h1>تنظیمات فعال سازی باشگاه مشتریان</h1>
		<?php
		if ( $configs::isDigitsInstalled() ) {
			$plugins['digits'] = array(
				'phoneBookId' => false,
				'name'        => __( 'Digits Plugin', 'farazsms-club' ),
								'phonebookAll'=>[]

			);

			if ( $configs->get_digits_configs() ) {
				$plugins['digits']['phonebookAll'] = $configs::getPhoneBooks( 'digits' );
			}
		}
		if ( $configs::isWooSmsInstalled() || $configs::isWooInstalled() ) {
			$plugins['woo'] = array(
				'phoneBookId' => false,
				'name'        => __( 'WooCommerce', 'farazsms-club' ),
				'phonebookAll'=>[]
			);
			if ( $configs->get_woo_sms_configs() || $configs::isWooInstalled()) {
				$plugins['woo']['phonebookAll'] = $configs::getPhoneBooks( 'woo' );
			}
		}
		?>
        <form method="post"></form>
        <table class="form-table">
                <?php foreach ($plugins as $key=>$plugin){ ?>
            <tr>
            <th style="alignment: right;text-align: right">
            <?php echo __('select phone books for ', 'farazsms-club').$plugin['name']?>
            </th>
            <td class="regular-text" style="text-align: right">
            <select type="number" multiple name="<?php echo $key;?>[]"  <?php if (sizeof($plugin['phonebookAll'])==0) {echo "disabled";}?>>
            <?php if (sizeof($plugin['phonebookAll'])>0) { foreach ($plugin['phonebookAll'] as $k=>$ph){
                $selected='';
                $title = $ph->title;
                $value = intval($ph->id);
                if(in_array($value,$options[$key])) {$selected="selected";}
                echo "<option value=$value $selected >$title</option>";
            }}?>
            </select>
            </td>
            </tr>

            <?php


}
		$save_btn = __('save','farazsms-club');
	    echo "<tr><th><input type='submit' value='$save_btn' /></th></tr></table></form>";
	    }
	            if(strlen($account)>1){
	        ?>
            <div>
            <form  method="post">
            <table class="form-table"><tr>
                <th style="text-align: right">نام کاربری</th>
                <td style="text-align: right"><input type="text" name="uname" value="<?php echo $options['uname']?>"></td>
                </tr><tr>
                <th style="text-align: right">رمزعبور</th>
                <td style="text-align: right"><input type="password" name="pass" value="<?php echo $options['pass']?>"></td>
                </tr>
                <tr>
                    <td><input type="submit" value="<?php echo __('save','farazsms-club')?>"></td>
                    </tr>
                </table>
    </form>
</div>
            <?php
	    }
	            echo "</div>";
	}

    function enqueue_select2_vue() {
		wp_register_style( 'select2css',
			FARAZSMS_CLUB_URL . 'assets/styles/select2.min.css', false, '4.0.13', 'all' );
		wp_register_style( 'bulma',
			FARAZSMS_CLUB_URL . 'assets/styles/bulma/bulma.min.css', false, '0.8.2', 'all' );
		wp_register_script( 'select2',
			FARAZSMS_CLUB_URL . 'assets/scripts/select2/select2.full.min.js', array( 'jquery' ), '4.0.13', true );
		wp_register_script( 'vue',
			FARAZSMS_CLUB_URL . 'assets/scripts/vue.js', array( 'jquery' ), '2.6', true );
//		wp_register_script( 'cities', FARAZSMS_CLUB_URL . 'assets/scripts/Cities.js', [ 'vue' ], '1', true );
//		wp_register_script( 'avenue', FARAZSMS_CLUB_URL . 'assets/scripts/Avenue.js', [ 'cities' ], '1', true );
//		wp_enqueue_script( 'cities' );
//		wp_enqueue_script( 'avenue' );
//		wp_enqueue_style( 'select2css' );
		wp_enqueue_style( 'bulma' );
//		wp_enqueue_script( 'select2' );
//		wp_enqueue_script( 'vue' );
	}

    function updated_user_meta( $null,$object_id, $meta_key, $meta_value){
	    if(strtolower($meta_key!=='digits_phone')) {return;}
	    $phone =intval(substr($meta_value,-10));
	    if ($phone < 9000000000  ){return;}
	    $user_id = intval($object_id);
	    $user=get_userdata($user_id);
	    $config=FARAZSMS_CLUB_CONFIG::getInstance();
	    $phone_books=$config::options()['digits'];
        foreach ($phone_books as $phone_book){
            if(FARAZSMS_CLUB_CONFIG::db_find_one($phone,$phone_book)) continue;
            $phones = array(                "phone"=>$phone,
	                                        "from_meta"=>'user',
	                                        "object_id"=>$object_id,
	                                        "f_name"=>$user->first_name,
	                                        "l_name"=>$user->last_name,
	                                        "phone_book"=>$phone_book
	                                        );
            if($config::save_to_digits_phone_book($phone,$phone_book)) $config::db_save($phones);
        }
    }
    function woo_payment_finished($id){
	    $order=get_post_meta($id);
	    $phone = $order['_billing_phone'];
	    if(!$phone)return;
	    $phone = intval(substr($phone[0],-10));
	    $config=FARAZSMS_CLUB_CONFIG::getInstance();
	    $phone_books=$config::options()['woo'];
	    foreach ($phone_books as $phone_book){
	        if(FARAZSMS_CLUB_CONFIG::db_find_one($phone,$phone_book)) continue;
            $phones = array(                "phone"=>$phone,
	                                        "from_meta"=>'post',
	                                        "object_id"=>$id,
	                                        "f_name"=>$order['_billing_first_name'],
	                                        "l_name"=>$order['_billing_last_name'],
	                                        "phone_book"=>$phone_book
	                                        );
            if($config::save_to_woo_phone_book($phone,$phone_book)) $config::db_save($phones);
	    }
	    self::err_log($order);
    }
}

FARAZSMS_CLUB::get_instance();








