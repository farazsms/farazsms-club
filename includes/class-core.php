<?php
/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

class FARAZSMS_CLUB extends FARAZSMS_CLUB_BASE {
	private static  $_instance = null;

	public function __construct() {
		$this->include_all();
		add_action( 'init', array( $this, 'add_languages_dir' ) );
		add_action('activate_plugin',[$this,'activate_plugin']);
		add_action( 'admin_menu', [ $this, 'settings' ] );
		add_filter('update_user_metadata',[$this,'updated_user_meta'],10,4);
		if(class_exists('WooCommerce'))
		    {add_action('woocommerce_thankyou',[$this,'woo_payment_finished']);}
    if (get_transient('farazsms-club-admin_notice')){
        add_action('admin_notices',[$this,'admin_notices_activated']);
    }
	}

	private function include_all() {
		if ( ! class_exists( "FARAZSMS_CLUB_CONFIG" ) ) {
			require_once( 'class-config.php' );
		}
	}

	static function activate_plugin(){
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
	    $this->enqueue_bulma();
		$configs = FARAZSMS_CLUB_CONFIG::getInstance();
	    $options = $configs::options();
		$plugins = array();
	    $tab=$_GET['tab'];
	    $help = '';
	    $phonebook = '';
	    $account = '';
	    $credit =$configs::get_credit();
	    if (strtolower($tab)=='phonebook'){$phonebook='class="is-active"';}
	    else if (strtolower($tab)=='account'){$account='class="is-active"';}
	    else {$help='class="is-active"';}
	    	if(!$credit && strlen($account) < 1 && $_SERVER['REQUEST_METHOD']  != 'POST'){
	        wp_redirect("?page=farazsms-club&tab=account");
	    }
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
			$options = $configs::options(true);
			$successful = __('successfully saved','farazsms-club');
			echo "<h3 class='is-success'>$successful</h3>";
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
                <?php _e('<h1> Guide to Customer Club </h1>
                <br>
                First, log in to your account in the SMS system above SMS, from the right menu> Phonebook> Group
                Create a new phonebook, create a unique code for each phonebook after creating the system phonebook
                Allows the person to put the phonebook with the username, password and code on this page and save the settings.
                From now on, the mobile number of WooCommerce and Digits customers will be automatically in the phone book of Faraz SMS SMS system.
                Your S will be saved. If you have not yet purchased the SMS system, use the farazsms-club discount code.
                <br>
                <br>','farazsms-club') ?>

                </div>
<?php } ?>
                <?php if(strlen($phonebook)> 0) {?>

        <form  method="post" style="direction: rtl">
            <table class="form-table">
                <h1><?php _e('Phonebook Configs','farazsms-club') ?></h1>
		<?php
		$plugins['general_phone_book'] = array(
				'phoneBookId' => false,
				'name'        => __( 'Genral phones Plugin', 'farazsms-club' ),
								'phonebookAll'=>$configs::getPhoneBooks( 'general_phone_book' )

			);
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
		$credit =$configs::get_credit();
	        ?>
            <div>
            <form  method="post">
            <table class="form-table">
            <?php if($credit) {?>
            <tr>
                <th style="text-align: right">
                    <?php _e('Credit','farazsms-club') ?>
                </th>

                <td style="text-align: right"><?php echo $credit ?></td>
                </tr>

            <?php } else{?>
            <th style="text-align: right">
            <?php _e('It seems user name and password are not working yet are you saved them?','farazsms-club') ?>
            </th>
            <?php }?>


            <tr>
                <th style="text-align: right"><?php _e('User Name','farazsms-club') ?></th>
                <td style="text-align: right"><input type="text" name="uname" value="<?php echo $options['uname']?>"></td>
                </tr><tr>
                <th style="text-align: right"><?php _e('password','farazsms-club') ?></th>
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

    function enqueue_bulma() {
		wp_register_style( 'bulma',
			FARAZSMS_CLUB_URL . 'assets/styles/bulma.min.css', false, '0.8.2', 'all' );
		wp_enqueue_style( 'bulma' );
	}

    function updated_user_meta( $null,$object_id, $meta_key, $meta_value){
	    $mobile_pattern = "/^(\s)*(\+98|0098|98|0)?(9\d{9})(\s*|$)/";
        preg_match($mobile_pattern,$meta_value,$matches);
	    if(strtolower($meta_key!=='digits_phone') && sizeof($matches) !== 5) {return;}
	    $phone =intval($matches[3]);
	    if ($phone < 9000000000  ){return;}
	    $user_id = intval($object_id);
	    $user=get_userdata($user_id);
	    $config=FARAZSMS_CLUB_CONFIG::getInstance();
	    $phone_books=$config::options()['general_phone_book'];
	    if(strtolower($meta_key!=='digits_phone')) $phone_books=$config::options()['digits'];
        foreach ($phone_books as $phone_book){
            if(FARAZSMS_CLUB_CONFIG::db_find_one($phone,$phone_book)) {continue;}
            $phones = array(                "phone"=>$phone,
	                                        "from_meta"=>'user',
	                                        "object_id"=>$object_id,
	                                        "f_name"=>$user->first_name,
	                                        "l_name"=>$user->last_name,
	                                        "phone_book"=>$phone_book
	                                        );
            if($config::save_to_digits_phone_book($phone,$phone_book)) {$config::db_save($phones);}
            elseif($config::save_to_general_phone_book($phone,$phone_book)) {$config::db_save($phones);}
        }
    }
    function woo_payment_finished($id){
	    $order=get_post_meta($id);
	    $phone = $order['_billing_phone'];
	    if(!$phone){return;}
	    $phone = intval(substr($phone[0],-10));
	    $config=FARAZSMS_CLUB_CONFIG::getInstance();
	    $phone_books=$config::options()['woo'];
	    foreach ($phone_books as $phone_book){
	        if(FARAZSMS_CLUB_CONFIG::db_find_one($phone,$phone_book)) {continue;}
            $phones = array(                "phone"=>$phone,
	                                        "from_meta"=>'post',
	                                        "object_id"=>$id,
	                                        "f_name"=>$order['_billing_first_name'],
	                                        "l_name"=>$order['_billing_last_name'],
	                                        "phone_book"=>$phone_book
	                                        );
            if($config::save_to_woo_phone_book($phone,$phone_book)) {$config::db_save($phones);}
	    }
	    self::err_log($order);
    }
}

FARAZSMS_CLUB::get_instance();








