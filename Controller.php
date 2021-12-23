<?php

namespace SendEmail;

use MapasCulturais\App;
use MapasCulturais\Entities\Opportunity;
use MapasCulturais\i;

/**
 * Registration Controller
 *
 * By default this controller is registered with the id 'registration'.
 *
 *  @property-read \MapasCulturais\Entities\Registration $requestedEntity The Requested Entity
 */
// class extends \MapasCulturais\Controllers\EntityController {
class Controller extends \MapasCulturais\Controllers\Registration
{

    public function GET_sendEmailResult(){
    
        
        //Método que verifica se a pessoa está logada
        $this->requireAuthentication();
        
        $app = APP::i();

        $request = $this->data;
            
        $opportunity = $app->repo("Opportunity")->find(['id'=>$request['opportunity_id']]);
        $opportunity->checkPermission('@control');
            
        $redirect_url = $app->request->getReferer();
        if(!$opportunity->publishedRegistrations){
            $app->redirect($redirect_url);
        }

        $registrations_ids = $this->getRegistrations($opportunity);

        //Definindo template a ser utilizado
        $template = 'registration_confirm_result';
        
        $dataValue = [];

        $ids = [];
        foreach ($registrations_ids as $value){
            $ids[] = $value['id'];
        }

        $template = $this->renderTemplate("views/sendemail", "registration_confirm_result.html");
        
        $mustache = new \Mustache_Engine();

        foreach ($ids as $id){
            
            $registration = $app->repo("Registration")->find(['id'=>$id]);

            if($registration->status <= 0){
                continue;
            }

            //var_dump($registration->getStatusNameById($registration->status));
            $content = $mustache->render($template,[
                'opportunity_css' =>$app->view->asset('sendemail/style.css', false),
                'opportunity_logo_saude' =>$app->view->asset('sendemail/img/logo-saude-email.png', false),
                'opportunity_rodape' =>$app->view->asset('sendemail/img/rodape-email.png', false),
                'opportunity_name' => $registration->opportunity->name,
                'project_name' => $registration->opportunity->ownerEntity->name,
                'status_final' =>$registration->getStatusNameById($registration->status),
                'opportunity_tracking_page' => $app->createUrl('oportunidade', $registration->opportunity->id)
            ]);
            //var_dump($content);
            //echo $content;
            
            
            $app->createAndSendMailMessage([
                'from' => $app->config['mailer.from'],
                'to' => $registration->owner->user->email,
                'subject' => i::__('Pulicação de Resultado'),
                'body' => $content
            ]);
            $app->log->debug("E-mail enviado para inscrição {$registration->id}");
            $app->em->clear();
        }
        $opportunity = $app->repo("Opportunity")->find(['id'=>$request['opportunity_id']]);
        //Configurando o atributo "sent_emails_results" para receber 1. O valor será salvo na tabela "opportunity_meta", coluna "value"
        $opportunity->sent_emails_results = 1;
        $opportunity->save(true);
        $app->redirect($redirect_url);
    }

    //Método que retorna as inscrições de uma oportunidade
    public function getRegistrations(Opportunity $opportunity){
        $app = App::i();

        $conn = $app->em->getConnection();
        
        $params = [];

        $query = "SELECT r.id FROM registration r WHERE r.opportunity_id = :opportunity_id";

        $params += [
            'opportunity_id' => $opportunity->id
        ];

        return $conn->fetchAll($query, $params);
    }

    //Método para renderizar o template
    public function renderTemplate($file_dir, $file_name){
        $app = App::i();
        $_file_name = $app->view->resolveFilename($file_dir, $file_name);        
        return file_get_contents($_file_name);
    }

}