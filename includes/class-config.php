<?php
/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( "FARAZSMS_CLUB_CONFIG" ) ) {
	class FARAZSMS_CLUB_CONFIG  extends FARAZSMS_CLUB_BASE {
		private static ?FARAZSMS_CLUB_CONFIG $_instance = null;
		private static string $_url = "https://ippanel.com/api/select";
		private static bool $_woo_sms_installed = false;
		private static bool $_woo_installed = false;
		private static bool $_digits_installed = false;
		private static array $_options = [];
		/*
		 * @var $_digits_phoneBookId int[]
		 * */
		private static array $_digits_phoneBookId;
		private static string $_digits_uname;
		private static string $_digits_password;
		/*
		 * @var $_woo_phoneBookId int[]
		 * */
		private static array $_woo_phoneBookId;
		private static string $_woo_uname;
		private static string $_woo_password;
		private static string $_number;
		private static ?string $_table_name = null;

		/**
		 * @return string
		 */
		public static function tableName(): string {
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
		public static function getInstance(): FARAZSMS_CLUB_CONFIG {
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
			$digit = get_option( "digit_ippanel" );
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
		public static function url(): string {
			return self::$_url;
		}

		/**
		 * @return bool
		 */
		public static function isWooSmsInstalled(): bool {
			return self::$_woo_sms_installed;
		}


		/**
		 * @return bool
		 */
		public static function isDigitsInstalled(): bool {
			return self::$_digits_installed;
		}

		/**
		 * @return bool
		 */
		public static function isWooInstalled(): bool {
			return self::$_woo_installed;
		}

		/**
		 * @return string
		 */
		public function digitsPhoneBookId(): string {
			return $this->_digits_phoneBookId;
		}


		/**
		 * @return string
		 */
		public static function digitsUname(): string {
			return self::$_digits_uname;
		}


		/**
		 * @return string
		 */
		public static function digitsPassword(): string {
			return self::$_digits_password;
		}


		/**
		 * @return array
		 */
		public static function wooPhoneBookId(): string {
			return self::$_woo_phoneBookId;
		}


		/**
		 * @return string
		 */
		public static function wooUname(): string {
			return self::$_woo_uname;
		}


		/**
		 * @return string
		 */
		public static function wooPassword(): string {
			return self::$_woo_password;
		}


		/**
		 * @return string
		 */
		public static function number(): string {
			return self::$_number;
		}

		/**
		 * @param string $number
		 */
		public static function setNumber( string $number ): void {
			self::$_number = $number;
		}

		static function getPhoneBooks( $plugin = 'digits' ) {
			if ( strpos( strtolower( $plugin ), 'digit' ) !== false ) {
				$uname = self::$_digits_uname;
				$pass  = self::$_digits_password;
			} elseif ( strtolower( $plugin ) == 'woo' ) {
				if ( self::isWooSmsInstalled() ) {
					$uname = self::$_woo_uname;
					$pass  = self::$_woo_password;
				} else {
					$uname = self::$_options['uname'];
					$pass  = self::$_options['pass'];
				}
			} else {
				return false;
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
					"digits" => array(),
					"woo"    => array(),
					"umame"  => array(),
					"pass"   => array(),
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
			}

			return $options;
		}

		/**
		 * @param bool $force force to get data from db
		 *
		 * @return array
		 */
		public static function options( $force = false ): array {
			if ( ! self::$_options || $force ) {
				$options = get_option( 'farazsms_options' );
				if ( ! $options ) {
					$options = array(
						"digits" => array(),
						"woo"    => array(),
						"umame"  => array(),
						"pass"   => array(),
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
			}

			return self::$_options;
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
 UNIQUE KEY `${table_name}_unique_phone_meta_id` (`phone`,`meta_id`,`phone_book`)
) $collate";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $query );
		}

		public static function db_find_one( $phone, $phone_book ) {
			global $wpdb;
			$table_name = self::tableName();
			$query      = "select * from $table_name where phone=$phone and phone_book=$phone_book";
			return      $wpdb->get_results( $query );
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
			return  $wpdb->insert( $table_name, $meta );
		}
		public static function save_to_digits_phone_book($phone,$phone_book){
			if(!self::isDigitsInstalled())return;
			$body = array(
				'uname'=>self::digitsUname(),
				'pass'=>self::digitsPassword(),
				'op'=>'phoneBookAdd',
				'phoneBookId'=>$phone_book,
				'number'=>$phone
			);

			$response = wp_remote_post(self::url(), array(
					'method' => 'POST',
					'headers' => [
						'Content-Type' => 'application/json',
					],
					'data_format' => 'body',
					'body' => json_encode($body)
				)
			);
			$response = json_decode($response['body']);
			if($response->status->code !== 0) return false;
			return true;
		}
		public static function save_to_woo_phone_book($phone,$phone_book){
			$uname = self::options()['uname'];
			$pass = self::options()['pass'];
			if(self::isWooSmsInstalled()){
				$uname=self::wooUname();
				$pass=self::wooPassword();
			};
			$body = array(
				'uname'=>$uname,
				'pass'=>$pass,
				'op'=>'phoneBookAdd',
				'phoneBookId'=>$phone_book,
				'number'=>$phone
			);

			$response = wp_remote_post(self::url(), array(
					'method' => 'POST',
					'headers' => [
						'Content-Type' => 'application/json',
					],
					'data_format' => 'body',
					'body' => json_encode($body)
				)
			);
			$response = json_decode($response['body']);
			if($response->status->code !== 0) return false;
			return true;
		}

	}


}
