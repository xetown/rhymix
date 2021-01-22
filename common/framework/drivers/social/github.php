<?php
namespace Rhymix\Framework\Drivers\Social;

// HECK: 임시 코드 라이브러리 추가되면 삭제 예정
include dirname(__DIR__) . '/social/vendor/autoload.php';
/**
 * Class Github
 * Base code by @YJSoft
 * @package Rhymix\Framework\Drivers\Social
 */
class Github extends Base implements \Rhymix\Framework\Drivers\SocialInterface
{
	public $oProvider = null;
	public $token = null;
	
	function getProvider()
	{
		if(!$this->oProvider)
		{
			$provider = new \League\OAuth2\Client\Provider\Github([
				'clientId'          => "{$this->config->github_client_id}",
				'clientSecret'      => "{$this->config->github_client_secret}",
				'redirectUri'       => getNotEncodedFullUrl('', 'module', 'sociallogin', 'act', 'procSocialloginCallback','service','github'),
			]);
			$this->oProvider = $provider;
		}
		
		return $this->oProvider;
	}
	
	/**
	 * @brief 인증 URL 생성
	 */
	function createAuthUrl(string $type = 'login'): string
	{
		$provider = $this->getProvider();

		$options = [
			'state' => $_SESSION['sociallogin_auth']['state'],
			'scope' => ['user','user:email']
		];
		
		return $provider->getAuthorizationUrl($options);
	}

	/**
	 * @brief 코드인증
	 */
	function authenticate()
	{
		$provider = $this->getProvider();
		
		$state = \Context::get('state');
		
		if(!$_SESSION['sociallogin_auth']['state'] || $state != $_SESSION['sociallogin_auth']['state'])
		{
			return new \BaseObject(-1, "msg_invalid_request");
		}

		$token = $provider->getAccessToken('authorization_code', [
			'code' => \Context::get('code'),
		]);
		
		if($token)
		{
			$this->token = $token;
		}

		// 토큰 삽입
		$_SESSION['sociallogin_driver_auth']['github'] = new \stdClass();
		$_SESSION['sociallogin_driver_auth']['github']->token['access'] = $token->getToken();
		
		unset($_SESSION['socialxe_auth_state']);

		return new \BaseObject();
	}

	function getSNSUserInfo()
	{
		if (!$_SESSION['sociallogin_driver_auth']['github']->token['access'])
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}

		$provider = $this->getProvider();

		$profile = $provider->getResourceOwner($this->token);
		$profile = $profile->toArray();
		if(!$profile)
		{
			return new \BaseObject(-1, 'msg_errer_api_connect');
		}
		
		// TODO : why do check empty value?
		if(!$profile['email'] || $profile['email'] == '')
		{
			return new \BaseObject(-1, '');
		}

		if($profile['email'])
		{
			$_SESSION['sociallogin_driver_auth']['github']->profile['email_address'] = $profile['email'];
		}
		else
		{
			return new \BaseObject(-1, 'msg_not_confirm_email_sns_for_sns');
		}
		
		$_SESSION['sociallogin_driver_auth']['github']->profile['sns_id'] = $profile['id'];
		$_SESSION['sociallogin_driver_auth']['github']->profile['user_name'] = $profile['login'];
		$_SESSION['sociallogin_driver_auth']['github']->profile['profile_image'] = $profile['avatar_url'];
		$_SESSION['sociallogin_driver_auth']['github']->profile['url'] = $profile['html_url'];
		$_SESSION['sociallogin_driver_auth']['github']->profile['etc'] = $profile;
		
		return new \BaseObject();
	}

	/**
	 * @brief 토큰파기
	 * @notice 미구현
	 */
	function revokeToken(string $access_token = '')
	{
		return;
	}

	function getProfileImage()
	{
		return $_SESSION['sociallogin_driver_auth']['github']->profile['profile_image'];
	}

	function requestAPI($request_url, $post_data = array(), $authorization = null, $delete = false)
	{
	}
}
