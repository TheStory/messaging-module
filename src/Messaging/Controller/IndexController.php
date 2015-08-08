<?php
/**
 * Copyright: STORY DESIGN Sp. z o.o.
 * Author: Yaroslav Shatkevich
 * Date: 08.08.2015
 * Time: 16:07
 */

namespace Messaging\Controller;

use Common\Controller\AbstractController;
use Messaging\Service\Mail;

class IndexController extends AbstractController
{
    public function renderAction()
    {
        if ($this->getRequest()->isPost()) {
            $template = $this->params()->fromPost('template');
            if (!$template) {
                return $this->newErrorModel('Template not provided', 422);
            }

//            var_dump($template);

            $data = $this->params()->fromPost('data', []);

            /** @var Mail $mail */
            $mail = $this->getServiceLocator()->get('mail');

            try {
                $this->getResponse()->setContent($mail->prepareBody($template, $data));
            } catch (\Exception $e) {
                return $this->newErrorModel($e->getMessage());
            }

            return $this->getResponse();
        } else {
            return $this->newErrorModel('Wrong method', 405);
        }
    }
}