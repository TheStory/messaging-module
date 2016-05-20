<?php

//
//  ginza-polls
//
//  Created by Yaroslav Shatkevich on 2014-07-31.
//  Copyright 2014 Story Design Sp. z o.o.. All rights reserved.
//

namespace Messaging\Service;

use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mime as Mime;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Model\ViewModel;
use ZfcTwig\View\TwigRenderer;

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
     * @param array $recipients array of target emails or to, cc, bcc email arrays
     * @param string $subject mail subject
     * @param string $template template name under template/ directory
     * @param array $variables template variables
     * @param array $attachments email att
     */
    public function send(
        array $recipients,
        $subject,
        $template,
        array $variables = array(),
        array $attachments = array()
    )
    {
        $body = $this->prepareBody($template, $variables);

        $appConfig = $this->getServiceLocator()->get('config');
        /* @var $config \Zend\Config\Config */
        $config = $appConfig['messaging'];

        $htmlBody = new MimePart($body);
        $htmlBody->type = 'text/html';
        $htmlBody->setCharset($config['encoding']);

        $parts = array($htmlBody);

        foreach ($attachments as $key => $attachment) {
            $parts[] = $this->createAttachment($key, $attachment);
        }

        $mimeMessage = new MimeMessage();
        $mimeMessage->setParts($parts);

        $message = new Message();
        $message->setFrom($config['from_email'], $config['from_name'])
            ->setEncoding($config['encoding'])
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
        
        if (isset($recipients['to'])) {
            $message->setTo($recipients['to']);
            
            if ($recipients['cc']) {
                $message->setCc($recipients['cc']);
            }
            
            if ($recipients['bcc']) {
                $message->setBcc($recipients['bcc']);
            }
            
            $smtp->send($message);
        } else {
            foreach ($recipients as $email) {
                $message->setTo($email);
                $smtp->send($message);
            }
        }
    }

    /**
     * Create attachment from file path
     *
     * @param string $fileName
     * @param string $filePath
     * @return MimePart
     */
    protected function createAttachment($fileName, $filePath)
    {
        $fileType = mime_content_type($filePath);
        $targetFileName = is_string($fileName) ? $fileName . '.' . pathinfo($filePath, PATHINFO_EXTENSION) : basename($filePath);

        $fileContent = fopen($filePath, 'r');
        $attachment = new MimePart($fileContent);
        $attachment->type = $fileType;
        $attachment->filename = $targetFileName;
        $attachment->disposition = Mime\Mime::DISPOSITION_ATTACHMENT;
        $attachment->encoding = Mime\Mime::ENCODING_BASE64;

        return $attachment;
    }

    /**
     * Inject variables into Twig template and HTML body for sending in email
     *
     * @param string $template Template path
     * @param array $variables
     * @return null|string
     * @throws \Exception If template not found
     */
    public function prepareBody($template, array $variables)
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate($template)
            ->setVariables($variables);

        /** @var TwigRenderer $renderer */
        $renderer = $this->getServiceLocator()->get('ZfcTwigRenderer');

        if (!$renderer->canRender($template)) {
            throw new \Exception('Template not found');
        }

        $body = $renderer->render($viewModel);

        return $body;
    }
}
