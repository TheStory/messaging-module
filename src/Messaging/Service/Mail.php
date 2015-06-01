<?php

//
//  ginza-polls
//
//  Created by Yaroslav Shatkevich on 2014-07-31.
//  Copyright 2014 Story Design Sp. z o.o.. All rights reserved.
//

namespace Messaging\Service;

use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\Mail\Transport\Smtp;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mail\Message;
Use Zend\Mime as Mime;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;
use Zend\View\Model\ViewModel;

/**
 * Mail sending service
 */
class Mail implements ServiceLocatorAwareInterface
{

	private $serviceLocator;

	/**
	 * @return \Zend\ServiceManager\ServiceLocatorInterface
	 */
	public function getServiceLocator()
	{
		return $this->serviceLocator;
	}

	/**
	 * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
	 */
	public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
	{
		$this->serviceLocator = $serviceLocator;
	}

	/**
	 * Send email with provided template
	 * @param array $recipients array of target emails
	 * @param string $subject mail subject
	 * @param string $template template name under template/ directory
	 * @param array $variables template variables
	 */
	public function send(
		array $recipients,
		$subject,
		$template,
		array $variables = array(),
		array $attachments = array()
	)
	{
		// render email template with provided variables

		$viewModel = new ViewModel();
		$viewModel->setTemplate($template)
				->setVariables($variables);

		$appConfig = $this->getServiceLocator()->get('config'); /* @var $config \Zend\Config\Config */
		$config = $appConfig['messaging'];

		// create new message instance and fill with data

		$body = $this->getServiceLocator()->get('ZfcTwigRenderer')->render($viewModel);

		$htmlBody = new MimePart($body);
		$htmlBody->type = 'text/html';

		$parts = array($htmlBody);

		foreach ($attachments as $attachment) {
			$parts[] = $this->createAttachment($attachment);
		}

		$mimeMessage = new MimeMessage();
		$mimeMessage->setParts($parts);

		$message = new Message();
		$message->setFrom($config['from_email'], $config['from_name'])
				->setEncoding('UTF-8')
				->setBody($mimeMessage)
				->setSubject($subject);

		// create SMTP transport, configure it

		$smtp = new Smtp(new SmtpOptions(array(
			'name' => $config['smtp_host'],
			'host' => $config['host'],
			'port' => $config['port'],
			'connection_class' => 'plain',
			'connection_config' => array(
				'username' => $config['username'],
				'password' => $config['password'],
				'ssl' => $config['ssl'],
			),
		)));

		// separate send of emails for each recipient

		foreach ($recipients as $email) {
			$message->setTo($email);
			$smtp->send($message);
		}
	}

	protected function createAttachment($filePath){
		$fileContent = fopen($filePath, 'r');
		$attachment = new MimePart($fileContent);
		$attachment->type = 'image/' . pathinfo($filePath, PATHINFO_EXTENSION);
		$attachment->filename = basename($filePath);
		$attachment->disposition = Mime\Mime::DISPOSITION_ATTACHMENT;
		$attachment->encoding = Mime\Mime::ENCODING_BASE64;

		return $attachment;
	}

}
