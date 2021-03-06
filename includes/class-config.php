<?php
/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( "FARAZSMS_CLUB_CONFIG" ) ) {
	class FARAZSMS_CLUB_CONFIG extends FARAZSMS_CLUB_BASE {
		private static $_instance = null;
		private static $_url = "https://ippanel.com/api/select";
		private static $_woo_sms_installed = false;
		private static $_woo_installed = false;
		private static $_digits_installed = false;
		private static $_options = [];
		/*
		 * @var $_digits_phoneBookId int[]
		 * */
		private static $_digits_phoneBookId;
		private static $_digits_uname;
		private static $_digits_password;
		/*
		 * @var $_woo_phoneBookId int[]
		 * */
		private static $_woo_phoneBookId;
		private static $_woo_uname;
		private static $_woo_password;
		private static $_number;
		private static $_table_name = null;

		/**
		 * @return string
		 */
		public static function tableName() {
			if ( ! self::$_table_name ) {
				global $wpdb;
				self::$_table_name = $wpdb->prefix . 'farazsms_contacts';
			}

			return self::$_table_name;
		}

		/**
		 * FARAZSMS_CLUB_CONFIG constructor.
		 *
		 */
		public function __construct() {

			if ( class_exists( 'WoocommerceIR_SMS_Gateways' ) ) {
				self::$_woo_sms_installed = true;
				$this->get_woo_sms_configs();
			}
			if ( class_exists( 'WooCommerce' ) ) {
				self::$_woo_installed = true;
			}
			if ( function_exists( 'digit_ready' ) ) {
				self::$_digits_installed = true;
				$this->get_digits_configs();
			}
			self::options();
		}


		/**
		 * @return FARAZSMS_CLUB_CONFIG
		 */
		public static function getInstance() {
			if ( ! self::$_instance ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		public static function get_woo_sms_configs() {
			$woo = get_option( "sms_main_settings" );
			if ( ! $woo ) {
				return false;
			}
			if ( strpos( strtolower( $woo['sms_gateway'] ), 'ippanel' ) ) {
				return false;
			}
			if ( ! $woo['sms_gateway_username'] || strlen( $woo['sms_gateway_username'] ) < 2 ) {
				return false;
			}
			self::$_woo_uname    = $woo['sms_gateway_username'];
			self::$_woo_password = $woo['sms_gateway_password'];

			return true;


		}

		public static function get_digits_configs() {
			$digit = get_option( "digit_farazsms" );
			if ( ! $digit ) {
				$digit = get_option( "digit_ippanel" );

			}
			if ( ! $digit ) {
				return false;
			}
			if ( ! $digit['username'] || strlen( $digit['username'] ) < 2 ) {
				return false;
			}
			self::$_digits_uname    = $digit['username'];
			self::$_digits_password = $digit['password'];

			return true;

		}

		/**
		 * @return string
		 */
		public static function url() {
			return self::$_url;
		}

		/**
		 * @return bool
		 */
		public static function isWooSmsInstalled() {
			return self::$_woo_sms_installed;
		}


		/**
		 * @return bool
		 */
		public static function isDigitsInstalled() {
			return self::$_digits_installed;
		}

		/**
		 * @return bool
		 */
		public static function isWooInstalled() {
			return self::$_woo_installed;
		}

		/**
		 * @return string
		 */
		public function digitsPhoneBookId() {
			return $this->_digits_phoneBookId;
		}


		/**
		 * @return string
		 */
		public static function digitsUname() {
			return self::$_digits_uname;
		}


		/**
		 * @return string
		 */
		public static function digitsPassword() {
			return self::$_digits_password;
		}


		/**
		 * @return array
		 */
		public static function wooPhoneBookId() {
			return self::$_woo_phoneBookId;
		}


		/**
		 * @return string
		 */
		public static function wooUname() {
			return self::$_woo_uname;
		}


		/**
		 * @return string
		 */
		public static function wooPassword() {
			return self::$_woo_password;
		}


		/**
		 * @return string
		 */
		public static function number() {
			return self::$_number;
		}

		/**
		 * @param string $number
		 */
		public static function setNumber( $number ) {
			self::$_number = $number;
		}

		static function getPhoneBooks( $plugin = 'digits' ) {
			if ( strpos( strtolower( $plugin ), 'digit' ) !== false ) {
				$uname = self::$_digits_uname;
				$pass  = self::$_digits_password;
			}
			elseif ( strtolower( $plugin ) == 'woo' ) {
				if ( self::isWooSmsInstalled() ) {
					$uname = self::$_woo_uname;
					$pass  = self::$_woo_password;
				} else {
					$uname = self::$_options['uname'];
					$pass  = self::$_options['pass'];
				}
			}
			else {
				$uname = self::options()['uname'];
				$pass  = self::options()['pass'];
			}
			$body = array(
				'uname' => $uname,
				'pass'  => $pass,
				'op'    => 'booklist'
			);
			$resp = wp_remote_post( self::$_url, array(
					'method'      => 'POST',
					'headers'     => [
						'Content-Type' => 'application/json',
					],
					'data_format' => 'body',
					'body'        => json_encode( $body )
				)
			);
			$resp = json_decode( $resp['body'] );
			if ( intval( $resp[0] ) != 0 ) {
				return false;
			}
			$resp = json_decode( $resp[1] );
			if ( strpos( strtolower( $plugin ), 'digit' ) !== false ) {
				self::$_digits_phoneBookId = $resp;
			} else {
				self::$_woo_phoneBookId = $resp;
			}

			return $resp;
		}

		function default_options( $force_update = false ) {

			$options = get_option( 'farazsms_options' );
			if ( ! $options ) {
				$options = array(
					"digits"             => array(),
					"woo"                => array(),
					"umame"              => array(),
					"pass"               => array(),
					"general_phone_book" => array(),
				);
				$config  = FARAZSMS_CLUB_CONFIG::getInstance();
				if ( $config::get_woo_sms_configs() !== false ) {
					$options['uname'] = $config::wooUname();
					$options['pass']  = $config::wooPassword();
				} elseif ( $config::get_digits_configs() !== false ) {
					$options['uname'] = $config::digitsUname();
					$options['pass']  = $config::digitsPassword();
				}
				add_option( 'farazsms_options', $options );
			} elseif ( ! $options['general_phone_book'] ) {
				$options['general_phone_book'] = array();
				update_option( 'farazsms_options', $options );
			}

			return $options;
		}

		/**
		 * @param bool $force force to get data from db
		 *
		 * @return array
		 */
		public static function options( $force = false ) {
			if ( ! self::$_options || $force ) {
				$options = get_option( 'farazsms_options' );
				if ( ! $options ) {
					$options = array(
						"digits"             => array(),
						"woo"                => array(),
						"umame"              => array(),
						"pass"               => array(),
						"general_phone_book" => array(),
					);
					if ( self::get_woo_sms_configs() !== false ) {
						$options['uname'] = self::wooUname();
						$options['pass']  = self::wooPassword();
					} elseif ( self::get_digits_configs() !== false ) {
						$options['uname'] = self::digitsUname();
						$options['pass']  = self::digitsPassword();
					}
					add_option( 'farazsms_options', $options );
				}
				self::$_options = $options;
				return $options;
			} elseif ( ! self::$_options['general_phone_book'] ) {
				self::$_options['general_phone_book'] = array();
				update_option( 'farazsms_options',  self::$_options );
			}

			return self::$_options;
		}

		public static function get_credit() {
			$options  = self::options();
			$body     = array(
				"uname" => $options['uname'],
				"pass"  => $options['pass'],
				'op'    => 'credit'
			);
			$response = wp_remote_post( self::url(), array(
					'method'      => 'POST',
					'headers'     => [
						'Content-Type' => 'application/json',
					],
					'data_format' => 'body',
					'body'        => json_encode( $body )
				)
			);
			$response = json_decode( $response['body'] );
			if ( $response[0] !== 0 ) {
				return false;
			}
			$credit_rial = explode( ".", $response[1] )[0];

			return substr( $credit_rial, 0, - 1 );
		}

		static function createdb() {
			global $wpdb;
			$table_name = self::tableName();
			$collate    = $wpdb->get_charset_collate();
			$query      = "CREATE TABLE IF NOT EXISTS `$table_name` (
 `id` int(10) NOT NULL AUTO_INCREMENT,
 `phone` BIGINT(10) UNSIGNED NOT NULL ,
 `from_meta` tinytext COLLATE utf8mb4_unicode_ci NOT NULL,
 `object_id` int(10) NOT NULL,
 `f_name` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 `l_name` tinytext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 `phone_book` int(10) DEFAULT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `${table_name}_unique_phone_meta_id` (`phone`,`object_id`,`phone_book`)
) $collate";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $query );
		}

		public static function db_find_one( $phone, $phone_book ) {
			global $wpdb;
			$table_name = self::tableName();
			$query      = sprintf( "select * from %s where phone=%s and phone_book=%s",
				$table_name, $phone, $phone_book );

			return $wpdb->get_results( $query );
		}

		/**
		 * @param $meta array((
		 * "phone"=>int,
		 * from_meta=>string,
		 * object_id=>int,
		 * f_name=>string|null,
		 * l_name=>string|null,
		 * phonebook=>int|null
		 * ))
		 */
		public static function db_save( $meta ) {
			global $wpdb;
			$table_name = self::tableName();

			return $wpdb->insert( $table_name, $meta );
		}

		public static function save_to_digits_phone_book( $phone, $phone_book ) {
			if ( ! self::isDigitsInstalled() ) {
				return;
			}
			$body = array(
				'uname'       => self::digitsUname(),
				'pass'        => self::digitsPassword(),
				'op'          => 'phoneBookAdd',
				'phoneBookId' => $phone_book,
				'number'      => $phone
			);

			$response = wp_remote_post( self::url(), array(
					'method'      => 'POST',
					'headers'     => [
						'Content-Type' => 'application/json',
					],
					'data_format' => 'body',
					'body'        => json_encode( $body )
				)
			);
			$response = json_decode( $response['body'] );
			if ( $response->status->code !== 0 ) {
				return false;
			}

			return true;
		}

		public static function save_to_woo_phone_book( $phone, $phone_book ) {
			$uname = self::options()['uname'];
			$pass  = self::options()['pass'];
			if ( self::isWooSmsInstalled() ) {
				$uname = self::wooUname();
				$pass  = self::wooPassword();
			}
			$body = array(
				'uname'       => $uname,
				'pass'        => $pass,
				'op'          => 'phoneBookAdd',
				'phoneBookId' => $phone_book,
				'number'      => $phone
			);

			$response = wp_remote_post( self::url(), array(
					'method'      => 'POST',
					'headers'     => [
						'Content-Type' => 'application/json',
					],
					'data_format' => 'body',
					'body'        => json_encode( $body )
				)
			);
			$response = json_decode( $response['body'] );
			if ( $response->status->code !== 0 ) {
				return false;
			}

			return true;
		}
		public static function save_to_general_phone_book( $phone, $phone_book ) {
			$uname = self::options()['uname'];
			$pass  = self::options()['pass'];
			$body = array(
				'uname'       => $uname,
				'pass'        => $pass,
				'op'          => 'phoneBookAdd',
				'phoneBookId' => $phone_book,
				'number'      => $phone
			);

			$response = wp_remote_post( self::url(), array(
					'method'      => 'POST',
					'headers'     => [
						'Content-Type' => 'application/json',
					],
					'data_format' => 'body',
					'body'        => json_encode( $body )
				)
			);
			$response = json_decode( $response['body'] );
			if ( $response->status->code !== 0 ) {
				return false;
			}

			return true;
		}

	}


}
