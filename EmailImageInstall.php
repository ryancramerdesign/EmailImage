<?php

class EmailImageInstall extends Wire {

	public function install(array $settings) {

		$field = $this->fields->get('email_image');
		if(!$field) {
			$field = new Field();
			$field->name = 'email_image';
			$field->type = $this->modules->get('FieldtypeImage');
			$field->label = 'Email Images';
			$field->extensions = 'jpeg jpg gif png';
			$field->maxFiles = 1; 
			$field->unzip = 0;
			$field->descriptionRows = 5;
			$field->entityEncode = 1; 
			$field->save();
			$this->message("Created field: $field->name"); 
		}
		$settings['images_field_id'] = $field->id; 

		// parent fieldgroup
		$parentFieldgroup = $this->fieldgroups->get('email-images');
		if(!$parentFieldgroup) {
			$parentFieldgroup = new Fieldgroup();
			$parentFieldgroup->name = 'email-images';
			$parentFieldgroup->save();
			$parentFieldgroup->add('title');
			$parentFieldgroup->save();
			$this->message("Created fieldgroup: $parentFieldgroup->name"); 
		}

		// parent template
		$parentTemplate = $this->templates->get('email-images'); 
		if(!$parentTemplate) {
			$parentTemplate = new Template();
			$parentTemplate->name = 'email-images';
			$parentTemplate->fieldgroup = $parentFieldgroup; 
			$parentTemplate->allowPageNum = 1; 
			$parentTemplate->save();
			$this->message("Created template: $parentTemplate->name"); 
		}
		$settings['parent_template_id'] = $parentTemplate->id; 

		// child fieldgroup
		$childFieldgroup = $this->fieldgroups->get('email-image');
		if(!$childFieldgroup) {
			$childFieldgroup = new Fieldgroup();
			$childFieldgroup->name = 'email-image';
			$childFieldgroup->save();
			$childFieldgroup->add('title');
			$childFieldgroup->add($field); // email_image
			$childFieldgroup->save();
			$this->message("Created fieldgroup: $childFieldgroup->name"); 
		}

		// child template
		$childTemplate = $this->templates->get('email-image'); 
		if(!$template) {
			$childTemplate = new Template();
			$childTemplate->name = 'email-image';
			$childTemplate->fieldgroup = $childFieldgroup; 
			$childTemplate->noChildren = 1; 
			$childTemplate->parentTemplates = array($parentTemplate->id); 
			$childTemplate->save();
			$this->message("Created template: $childTemplate->name"); 
		}
		$settings['child_template_id'] = $childTemplate->id; 

		$parentPage = $this->pages->get('/email-images/'); 
		if(!$parentPage->id) {
			$parentPage = new Page();
			$parentPage->template = $parentTemplate;
			$parentPage->parent = '/';
			$parentPage->name = 'email-images';
			$parentPage->title = 'Email Images';
			$parentPage->addStatus(Page::statusHidden); 
			$parentPage->sortfield = '-created';
			$parentPage->save();
			$this->message("Created page: $parentPage->path"); 
		}
		$settings['parent_page_id'] = $parentPage->id; 

		// update settings for parentTemplate
		$parentTemplate->childTemplates = array($childTemplate->id);
		$parentTemplate->noParents = 1; 
		$parentTemplate->save();

		$this->modules->saveModuleConfigData('EmailImage', $settings); 	

		// install template file
		if(is_writable(wire('config')->paths->templates)) {
			if(@copy(dirname(__FILE__) . '/email-images.php', wire('config')->paths->templates . 'email-images.php')) {
				$this->message("Installed template file: email-images.php"); 
			}
		}	

	}

	public function uninstall(EmailImage $module) {

		if($module->parent_page_id) {
			$parentPage = $this->pages->get((int) $module->parent_page_id); 
			if($parentPage->id) try {
				$this->pages->delete($parentPage, true);
				$this->message("Deleted parent page"); 
			} catch(Exception $e) {
				$this->error($e->getMessage());
			}
		}

		if($module->child_template_id) {
			$childTemplate = $this->templates->get((int) $module->child_template_id); 
			if($childTemplate) try {
				$fieldgroup = $childTemplate->fieldgroup;
				$this->templates->delete($childTemplate);
				$this->fieldgroups->delete($fieldgroup);
				$this->message("Deleted parent template/fieldgroup"); 
			} catch(Exception $e) {
				$this->error($e->getMessage());
			}
		}

		if($module->parent_template_id) {
			$parentTemplate = $this->templates->get((int) $module->parent_template_id); 
			if($parentTemplate) try {
				$fieldgroup = $parentTemplate->fieldgroup;
				$this->templates->delete($parentTemplate);
				$this->fieldgroups->delete($fieldgroup);
				$this->message("Deleted child template/fieldgroup"); 
			} catch(Exception $e) {
				$this->error($e->getMessage());
			}
		}

		if($module->images_field_id) {
			$field = $this->fields->get((int) $module->images_field_id); 
			if($field) try {
				$this->fields->delete($field); 
				$this->message("Deleted email_image field"); 
			} catch(Exception $e) {
				$this->error($e->getMessage());
			}
		}	

		try {
			$dir = $module->getTmpDir();
			if(is_dir($dir)) { 
				wireRmdir($dir, true);
				$this->message("Removed tmp dir: $dir"); 
			}
		} catch(Exception $e) {
			$this->error($e->getMessage());
		}
	}
}
