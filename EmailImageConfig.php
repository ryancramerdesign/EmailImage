<?php

class EmailImageConfig extends Wire {

	public function getConfig(array $data) {

		// check that they have the required PW version
		if(version_compare(wire('config')->version, '2.2.13', '<')) {
			$this->error("Email Image requires ProcessWire 2.2.13 or newer. Please update.");
		}

		$modules = wire('modules');
		$form = new InputfieldWrapper();

		$field = $modules->get("InputfieldText"); 
		$field->attr('name', 'pop3_hostname'); 
		$field->attr('value', $data['pop3_hostname']); 
		$field->label = __('POP3 hostname');
		$field->columnWidth = 50;
		$field->required = true; 
		$form->add($field); 

		$field = $modules->get("InputfieldInteger"); 
		$field->attr('name', 'pop3_port'); 
		$field->attr('value', $data['pop3_port']); 
		$field->label = __('POP3 port');
		$field->columnWidth = 20;
		$field->required = true; 
		$form->add($field); 

		$field = $modules->get("InputfieldInteger"); 
		$field->attr('name', 'wait_seconds'); 
		$field->attr('value', $data['wait_seconds']); 
		$field->label = __('Check every # seconds');
		$field->columnWidth = 30;
		$field->required = true; 
		$form->add($field); 

		$field = $modules->get("InputfieldText"); 
		$field->attr('name', 'pop3_user'); 
		$field->attr('value', $data['pop3_user']); 
		$field->label = __('POP3 user');
		$field->columnWidth = 50;
		$field->required = true; 
		$form->add($field); 

		$field = $modules->get("InputfieldText"); 
		$field->attr('name', 'pop3_password'); 
		$field->attr('value', $data['pop3_password']); 
		$field->attr('type', 'password'); 
		$field->label = __('POP3 password');
		$field->columnWidth = 50;
		$field->required = true; 
		$form->add($field); 

		$field = $modules->get("InputfieldTextarea"); 
		$field->attr('name', 'valid_senders'); 
		$field->attr('value', $data['valid_senders']); 
		$field->label = __('Valid senders');
		$field->description = __('Enter a list of email addresses (1 per line) to process emails from.'); 
		$form->add($field); 

		$fieldset = $modules->get('InputfieldFieldset'); 
		$fieldset->label = __('Advanced'); 
		$fieldset->attr('name', '_advanced'); 
		$fieldset->collapsed = Inputfield::collapsedYes; 

		$field = $modules->get('InputfieldCheckbox'); 
		$field->attr('name', 'pop3_apop'); 
		$field->attr('value', 1); 
		$field->attr('checked', $data['pop3_apop'] ? 'checked' : ''); 
		$field->columnWidth = 50;
		$field->label = __('Use APOP?'); 
		$field->notes = __('In rare cases this may be required.'); 
		$fieldset->add($field); 

		$field = $modules->get('InputfieldCheckbox'); 
		$field->attr('name', 'pop3_tls'); 
		$field->attr('value', 1); 
		$field->attr('checked', $data['pop3_tls'] ? 'checked' : ''); 
		$field->columnWidth = 50;
		$field->label = __('Use TLS?'); 
		$field->notes = __('GMail uses TLS and port 993.'); 
		$fieldset->add($field); 

		$field = $modules->get("InputfieldText"); 
		$field->attr('name', 'pop3_body_txt_start'); 
		$field->attr('value', $data['pop3_body_txt_start']); 
		$field->label = __('Tag or word that starts email body text for image description');
		$field->notes = __('Example: [text]'); 
		$field->columnWidth = 50;
		$fieldset->add($field); 

		$field = $modules->get("InputfieldText"); 
		$field->attr('name', 'pop3_body_txt_end'); 
		$field->attr('value', $data['pop3_body_txt_end']); 
		$field->label = __('Tag or word that ends email body text for image description');
		$field->notes = __('Example: [/text]'); 
		$field->columnWidth = 50;
		$fieldset->add($field); 

		$form->add($fieldset); 

		$field = $modules->get('InputfieldCheckbox');
		$field->attr('name', '_test_settings');
		$field->label = __('Test settings now');
		$field->attr('value', 1);
		$field->attr('checked', '');
		$form->add($field); 

		if(wire('session')->test_settings) {
			wire('session')->remove('test_settings'); 
			$field->notes = $this->testSettings();

		} else if(wire('input')->post->_test_settings) {
			wire('session')->set('test_settings', 1); 
		}

		$file = wire('config')->paths->templates . 'email-images.php';
		if(!is_file($file)) $this->error("Please copy the file /site/modules/EmailImage/email-images.php to /site/templates/email-images.php"); 

		return $form;
	}

	public function testSettings() {

		$errors = array();
		$success = false; 
		$module = wire('modules')->get('EmailImage');

		try {
			$a = $module->getAdaptor();
			$success = $a->testConnection();
			
		} catch(Exception $e) {
			$errors[] = $e->getMessage(); 
		}

		if($success) {
			$note = $this->_('SUCCESS! Email settings appear to work correctly.');
			$this->message($note); 

		} else {
			$note = $this->_('ERROR: email settings did not work.'); 	
			$this->error($note);
			foreach($a->getErrors() as $error) $this->error($error); 
		}

		return $note; 	
	}
}
