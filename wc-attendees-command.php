<?php

use WP_CLI\Utils;

class WC_Attendees_Command extends WP_CLI_Command {

	/**
	 * Prepare a list of all WC attendees
	 *
	 * @when before_wp_load
	 *
	 * <url>
	 * : A URL where Gravatar hashes can be harvested from.
	 *
	 * [--format=<format>]
	 * : Format output as table, CSV or JSON. Default: table.
	 *
	 * ## EXAMPLES
	 *
	 *     wp --require=wc-attendees-command.php wc-attendees https://portland.wordcamp.org/2015/attendees/ --format=csv > attendees.csv
	 */
	public function __invoke( $args, $assoc_args ) {

		$defaults = array(
			'format'     => 'table',
			);
		$assoc_args = array_merge( $defaults, $assoc_args );

		$attendees = $fields = array();

		$response = Utils\http_request( 'GET', $args[0] );
		$dom = new DOMDocument;
		@$dom->loadHtml( $response->body ); // suppress html5 errors
		foreach( $dom->getElementsByTagName( 'img' ) as $img ) {
			$src = $img->getAttribute( 'src' );
			if ( 'secure.gravatar.com' !== parse_url( $src, PHP_URL_HOST ) ) {
				continue;
			}
			$hash = str_replace( '/avatar/', '', parse_url( $src, PHP_URL_PATH ) );
			$request_url = sprintf( 'https://secure.gravatar.com/%s.json', $hash );
			$response = Utils\http_request( 'GET', $request_url );
			if ( 200 !== $response->status_code ) {
				WP_CLI::warning( "Couldn't fetch {$request_url} (HTTP {$response->status_code})" );
				continue;
			}
			$data = json_decode( $response->body );
			$attendee = array(
				'display_name'       => $data->entry[0]->displayName,
				'description'        => isset( $data->entry[0]->aboutMe ) ? $data->entry[0]->aboutMe : '',
				'location'           => isset( $data->entry[0]->currentLocation ) ? $data->entry[0]->currentLocation : '',
				);
			if ( ! empty( $data->entry[0]->emails ) ) {
				foreach( $data->entry[0]->emails as $email ) {
					if ( ! $email->primary ) {
						continue;
					}
					$attendee['email'] = $email->value;
				}
			}
			if ( ! empty( $data->entry[0]->accounts ) ) {
				foreach( $data->entry[0]->accounts as $account ) {
					$key = 'wordpress' === $account->shortname ? 'website' : $account->shortname;
					$attendee[ $key ] = $account->url;
				}
			}
			foreach( $attendee as $key => $value ) {
				if ( ! in_array( $key, $fields ) ) {
					$fields[] = $key;
				}
			}
			$attendees[] = $attendee;
		}
		foreach( $attendees as &$attendee ) {
			foreach( $fields as $field ) {
				if ( ! array_key_exists( $field, $attendee ) ) {
					$attendee[ $field ] = '';
				}
			}
		}
		Utils\format_items( $assoc_args['format'], $attendees, $fields );
	}

}

WP_CLI::add_command( 'wc-attendees', 'WC_Attendees_Command' );
