<?php

namespace DMS\Includes\Services;

use WP_REST_Request;
use WP_User;

class Auth_Service {

	/**
	 * Current Rest request instance
	 *
	 * @var WP_REST_Request
	 */
	private WP_REST_Request $request;

	/**
	 * Constructor to initialize the Auth_Service with the request object.
	 *
	 * @param WP_REST_Request $request
	 */
	public function __construct( WP_REST_Request $request ) {
		$this->set_request( $request );
	}

	/**
	 * Request setter
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return void
	 */
	public function set_request( WP_REST_Request $request ): void {
		$this->request = $request;
	}

	/**
	 * Main method to authorize the current request.
	 *
	 * @return bool True if authorized, false otherwise.
	 */
	public function authorize(): bool {
		$authorized = false;
		if ( $this->is_logged_in_admin() ) {
			$authorized = $this->verify_nonce();
		}

		return $authorized ?: $this->authenticate_with_app_password();
	}

	/**
	 * Checks if the current user is logged in and has administrator privileges.
	 *
	 * @return bool True if the user is an admin and logged in, false otherwise.
	 */
	private function is_logged_in_admin(): bool {
		return is_user_logged_in() && current_user_can( 'manage_options' );
	}

	/**
	 * Verifies the nonce for logged-in requests.
	 *
	 * @return bool True if the nonce is valid, false otherwise.
	 */
	private function verify_nonce(): bool {
		$nonce = $this->request->get_header( 'X-WP-Nonce' );

		return $nonce && wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * Authenticates non-logged-in requests using Basic Authentication with app passwords.
	 *
	 * @return bool True if authenticated as an admin user, false otherwise.
	 */
	private function authenticate_with_app_password(): bool {
		$auth_header = $this->get_auth_header();

		if ( ! $auth_header ) {
			return false;
		}

		list( $username, $app_password ) = $this->extract_credentials( $auth_header );
		$user = $this->authenticate_user( $username, $app_password );

		if ( !($user instanceof WP_User ) || ! $this->is_user_admin( $user )) {
			return false;
		}

		return true;
	}

	/**
	 * Retrieves the authorization header from the request.
	 *
	 * @return string|null The authorization header or null if not present.
	 */
	private function get_auth_header(): ?string {
		return $this->request->get_header( 'Authorization' );
	}

	/**
	 * Decodes and extracts the username and password from the authorization header.
	 *
	 * @param string $auth_header
	 *
	 * @return array [username, app_password]
	 */
	private function extract_credentials( string $auth_header ): array {
		return explode( ':', base64_decode( str_replace( 'Basic ', '', $auth_header ) ) );
	}

	/**
	 * Authenticates a user using the provided credentials.
	 *
	 * @param string $username
	 * @param string $app_password
	 *
	 * @return WP_User|false The authenticated user object, or false on failure.
	 */
	private function authenticate_user( string $username, string $app_password ) {
		$user = wp_authenticate( $username, $app_password );

		return is_wp_error( $user ) ? false : $user;
	}

	/**
	 * Checks if a user has the administrator role.
	 *
	 * @param WP_User $user
	 *
	 * @return bool True if the user has an administrator role, false otherwise.
	 */
	private function is_user_admin( WP_User $user ): bool {
		return in_array( 'administrator', $user->roles, true );
	}
}
