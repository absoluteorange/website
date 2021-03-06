<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Controller for oauth
 *
 * @package		Auth
 *
 */
class Auth extends CI_Controller
{

	public $twitterKey= 'iKGq68YjeRv7LTxx1GKA';
    public $twitterSecret = 'VcWiq18dwkhSdCKbnIVRG3H2QC3J0FOYp3cjGI8';

    public function oauth($provider)
    {
        $this->load->helper('url');
        $this->load->library('oauth/OAuth');

        if ($this->uri->segment(3) == 'twitter'):
        	$config['key']= $this->twitterKey;
        	$config['secret'] = $this->twitterSecret;
        endif;

        // Create an consumer from the config
        $consumer = $this->oauth->consumer(array(
            'key' => $config['key'],
            'secret' => $config['secret'],
        ));

        // Load the provider
        $provider = $this->oauth->provider($provider);


        // Create the URL to return the user to
        $callback = site_url('auth/oauth/'.$provider->name);

        if ( ! $this->input->get_post('oauth_token'))
        {
            // Add the callback URL to the consumer
            $consumer->callback($callback);

            // Get a request token for the consumer
            $token = $provider->request_token($consumer);

            // Store the token
            $this->session->set_userdata('oauth_token', base64_encode(serialize($token)));

            // Get the URL to the twitter login page
            $url = $provider->authorize($token, array(
                'oauth_callback' => $callback,
            ));

            // Send the user off to login
            redirect($url);
        }
        else
        {
            if ($this->session->userdata('oauth_token'))
            {
                // Get the token from storage
                $token = unserialize(base64_decode($this->session->userdata('oauth_token')));
            }

            if ( ! empty($token) AND $token->access_token !== $this->input->get_post('oauth_token'))
            {
                // Delete the token, it is not valid
                $this->session->unset_userdata('oauth_token');

                // Send the user back to the beginning
                exit('invalid token after coming back to site');
            }

            // Get the verifier
            $verifier = $this->input->get_post('oauth_verifier');

            // Store the verifier in the token
            $token->verifier($verifier);

            // Exchange the request token for an access token
            $token = $provider->access_token($consumer, $token);

            // We got the token, let's get some user data
  			if ($this->uri->segment(3) == 'twitter'):
            	$user = $provider->get_user_info($consumer, $token);
           		$tweets = $provider->get_user_tweets($consumer, $token);
           		$twitterInfo = array (
	           		'user' => $user,
	           		'tweets' => $tweets
           		);
           		file_put_contents('./tweets.txt', json_encode($twitterInfo));
           	endif;


            // Here you should use this information to A) look for a user B) help a new user sign up with existing data.
            // If you store it all in a cookie and redirect to a registration page this is crazy-simple.
            /*echo "<pre>Tokens: ";
            var_dump($token).PHP_EOL.PHP_EOL;
            echo "User Info: ";
            var_dump($user);*/
        }
    }
}
?>