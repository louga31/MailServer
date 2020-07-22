<?php

class ChangePasswordMailserverDriver implements \RainLoop\Providers\ChangePassword\ChangePasswordInterface
{
	/**
	 * @var string
	 */
	private $sAllowedEmails = '';

	/**
	 * @param string $sAllowedEmails
	 *
	 * @return \ChangePasswordMailserverDriver
	 */
	public function SetAllowedEmails($sAllowedEmails)
	{
		$this->sAllowedEmails = $sAllowedEmails;
		return $this;
	}

	/**
	 * @param \MailSo\Log\Logger $oLogger
	 *
	 * @return \ChangePasswordMailserverDriver
	 */
	public function SetLogger($oLogger)
	{
		if ($oLogger instanceof \MailSo\Log\Logger)
		{
			$this->oLogger = $oLogger;
		}

		return $this;
	}

	/**
	 * @param \RainLoop\Model\Account $oAccount
	 *
	 * @return bool
	 */
	public function PasswordChangePossibility($oAccount)
	{
		return $oAccount && $oAccount->Email() &&
			\RainLoop\Plugins\Helper::ValidateWildcardValues($oAccount->Email(), $this->sAllowedEmails);
	}

	/**
	 * @param \RainLoop\Model\Account $oAccount
	 * @param string $sPrevPassword
	 * @param string $sNewPassword
	 *
	 * @return bool
	 */
	public function ChangePassword(\RainLoop\Account $oAccount, $sPrevPassword, $sNewPassword)
	{
		if ($this->oLogger)
		{
			$this->oLogger->Write('Trying to change password for '.$oAccount->Email());
		}

		$bResult = false;
		$sEmail = $oAccount->Email();
		$sEmailUser = \MailSo\Base\Utils::GetAccountNameFromEmail($sEmail);
		$sEmailDomain = \MailSo\Base\Utils::GetDomainFromEmail($sEmail);

		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => "http://127.0.0.1:3000/users/" . urlencode($sEmail),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS =>"{\r\n   \"oldPassword\": \"" . $sPrevPassword . "\",\r\n   \"newPassword\": \"" . $sNewPassword . "\"\r\n}",
			CURLOPT_HTTPHEADER => array(
				"Content-Type: application/json"
			),
		));

		$response = curl_exec($curl);
		curl_close($curl);

		$json = json_decode($response, true);

		$bResult = $json['success'];

		return $bResult;
	}
}