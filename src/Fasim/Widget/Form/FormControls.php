<?php
namespace Fasim\Widget\Form;

interface FormControl {
	function render();
}

class FormHtml implements FormControl {
	public $html = '';

	public function __construct($html='') {
		$this->html($html);
	}

	public function html($html='') {
		$this->html = $html;
		return $this;
	}

	public function render() {
		return $this->html;
	}
}

class FormScript extends FormHtml {
	public function render() {
		$html = '<script type="text/javascript">'."\n";
		$html .= $this->html."\n";
		$html .= '</script>'."\n";
		return $html;
	}
}

abstract class FormValue implements FormControl {
	public $label = '';
	public $key = '';
	public $value = '';
	public $readonly = false;
	

	public $rules = [];
	
	public $min = 0;
	public $max = 0;

	public $errorWord = '';
	public $errorType = '';

	public function __construct($key='') {
		$this->key($key);
	}

	public function label($label) {
		$this->label = $label;
		return $this;
	}

	public function key($key) {
		$this->key = $key;
		return $this;
	}

	public function value($value) {
		$this->value = $value;
		return $this;
	}

	public function readonly($readonly=true) {
		$this->readonly = $readonly;
		return $this;
	}

	public function notEmpty() {
		$this->rules[] = 'not_empty';
		return $this;
	}

	public function integerValue() {
		$this->rules[] = 'integer';
		return $this;
	}

	public function numbericValue() {
		$this->rules[] = 'numberic';
		return $this;
	}

	public function urlValue() {
		$this->rules[] = 'url';
		return $this;
	}

	public function emailValue() {
		$this->rules[] = 'email';
		return $this;
	}

	public function min($min) {
		$this->min = $min;
		return $this;
	}

	public function max($max) {
		$this->max = $max;
		return $this;
	}

	public function addRule($rule) {
		$this->rules[] = $rule;
		return $this;
	}

	public function getError() {

		if ($this->errorType == '') {
			return '';
		}
		if ($this->errorWord != '') {
			return $this->errorWord;
		}
		switch ($this->errorType) {
			case 'min':
				return '长度必须大于'.$this->min;
			case 'max':
				return '长度必须小于'.$this->max;
			case 'not_empty':
				return '不能为空';
			case 'integer':
				return '必须是整数';
			case 'numberic':
				return '必须是数字';
			case 'url':
				return '必须是网址';
			case 'email':
				return '必须是Email';
		}
		return '格式错误';
	}

	public function error($errorWord) {
		$this->errorWord = $errorWord;
	}

	public function addCustomError($errorWord) {
		$this->errorType = 'custom';
		$this->errorWord = $errorWord;
	}

	public function checkRules($value) {
		if (strlen($value.'') < $this->min) {
			$this->errorType = 'min';
			return false;
		}
		if ($this->max > 0 && strlen($value.'') > $this->max) {
			$this->errorType = 'max';
			return false;
		}
		foreach ($this->rules as $rule) {
			$result = $this->checkRule($rule, $value);
			if (!$result) {
				$this->errorType = $rule;
				return false;
			}
		}
		$this->errorType = '';
		return true;
	}

	public function checkRule($rule, $value) {
		if ($rule == 'not_empty') {
			if (empty($value)) {
				return false;
			}
		} else if (!empty($value)){
			$p = $rule;
			if ($rule == 'integer') {
				$p = '/^\d+$/s';
			} else if ($rule == 'numberic') {
				$p = '/^\d+\.?\d*$/s';
			} else if ($rule == 'email') {
				return filter_var($value, FILTER_VALIDATE_EMAIL);
			} else if ($rule == 'url') {
				return filter_var($value, FILTER_VALIDATE_URL);
			}
			if (!preg_match($p, $value)) {
				return false;
			}
		}
		return true;
	}
}


class FormButton implements FormControl {
	public $name;
	public $link = '';
	public $primary = false;
	public function __construct($name='') {
		$this->name = $name;
	}

	public function name($name) {
		$this->name = $name;
		return $this;
	}

	public function link($url) {
		$this->primary = false;
		$this->url = $url;
		return $this;
	}
	public function primary() {
		$this->primary = true;
		return $this;
	}

	public function render() {
		if ($this->primary) {
			return "<button class=\"btn btn-primary\"><i class=\"fa fa-save\"></i> {$this->name}</button> \n";
		} else if ($this->url != '') {
			$url = FormBuilder::getUrl($this->url);
			return "<a href=\"{$url}\" class=\"btn\">{$this->name}</a> \n";
		}
	}

}

class FormHidden extends FormValue {

	public function render() {
		return  "<input type=\"hidden\" name=\"n_{$this->key}\" value=\"{$this->value}\" /> \n";
	}

}

class FormGroup extends FormValue {
	
	public $remark = '';
	
	public function remark($remark) {
		$this->remark = $remark;
		return $this;
	}

	public function render() {
		$error = $this->getError();
		$errorClass = $error == '' ? '' : ' error';
		$html =  "<div class=\"control-group{$errorClass}\"> \n";
		$html .=  "<label class=\"control-label\" for=\"i_{$this->key}\">{$this->label}</label> \n";
		$html .=  "<div class=\"controls\"> \n";
		$html .=  $this->renderInput();
		if ($error != '') {
			$html .=  "<span class=\"help-inline\">{$error}</span> \n";
		}
		if ($this->remark) {
			$html .=  "<span class=\"tip\">{$this->remark}</span> \n";
		}
		$html .=  "</div> \n";
		$html .=  "</div> \n";
		return $html;
	}

	public function renderInput() {
		return '';
	}
}

class FormImages extends FormGroup {
	public $maxCount = 6;
	public $allowFiles = 'jpg,jpeg,gif,png';

	public function maxCount($maxCount) {
		$this->maxCount = $maxCount;
	}

	public function allowFiles($allowFiles) {
		$this->allowFiles = $allowFiles;
	}


	public function renderInput() {
		$fileId = 'i_'.$this->key;
		$html = "<input type=\"hidden\" id=\"{$fileId}\" name=\"n_{$this->key}\" value=\"{$this->value}\" /> \n";
		$fileListId = 'fileList_'.$this->key;
		$filePickerId = 'filePicker_'.$this->key;
		$images = empty($this->value) ? [] : explode(';', $this->value);
		$html .= '<div class="webuploader clearfix">'."\n";
		$html .= '<div id="'.$fileListId.'" class="uploader-list">'."\n";
		foreach ($images as $image) {
			$html .= '<div class="file-item"><img src="'.$image.'" /><i class="fa fa-close"></i></div>'."\n";
		}
		$html .= '</div>'."\n";
		$html .= '<div id="'.$filePickerId.'" class="file-upload-btn"><i class="fa fa-plus fa-3x"></i><br />上传图片</div>'."\n";
		$html .= '</div>'."\n";
		$html .= <<<EOT
<script type="text/javascript">
$('body').ready(function(){
	var maxCount = {$this->maxCount};
	var uploader = WebUploader.create({
		auto: true,
		server: '/attachment/upload',
		pick: '#{$filePickerId}',
		accept: {
			title: '上传文件',
			extensions: '{$this->allowFiles}',
			mimeTypes: 'image/*'
		}
	});
	function checkCount() {
		var items = $('#{$fileListId} .file-item');
		$('#{$filePickerId}').toggle(items.length < maxCount);
	}
	checkCount();
	function removeItem(btn) {
		var item = $(btn).closest('div');
		var index = $('#{$fileListId} .file-item').index(item);
		item.remove();
		var value = $('#{$fileId}').val().split(';');
		value.splice(index, 1);
		$('#{$fileId}').val(value.join(';'));
		checkCount();
	}
	$('#{$fileListId} .file-item i').click(function(){
		removeItem(this);
	});
	uploader.on('fileQueued', function( file ) {
		var li = $(
				'<div id="' + file.id + '" class="file-item">' +
					'<img>' +
					'<i class="fa fa-close"></i>' +
				'</div>'
				),
			img = li.find('img');
		$('#{$fileListId}').append(li);
		checkCount();
		li.find('i').click(function(){
			removeItem(this);
		});
		uploader.makeThumb(file, function( error, src ) {
			if ( error ) {
				img.replaceWith('<span>不能预览</span>');
				return;
			}
			img.attr( 'src', src );
		}, 160, 160 );
	});
	uploader.on('uploadProgress', function( file, percentage ) {
		var li = $( '#'+file.id ), percentDiv = li.find('.progress span');
		if ( !percentDiv.length ) {
			percentDiv = $('<p class="progress"><span></span></p>').appendTo( li ).find('span');
		}
		percentDiv.css('width', percentage * 100 + '%' );
	});
	function showError(fileId, msg) {
		console.log(fileId);
		var li = $('#'+fileId), errorDiv = li.find('p.error');
		if ( !errorDiv.length ) {
			errorDiv = $('<p class="error"></p>').appendTo( li );
		}
		errorDiv.text(msg);
	}
	uploader.on('uploadSuccess', function( file, response ) {
		//$( '#'+file.id ).addClass('upload-state-done');
		if (typeof response == 'object') {
			if (response.error == 0) {
				var value = $('#{$fileId}').val();
				if (value != '') {
					value = value + ';' + response.url;
				} else {
					value = response.url;
				}
				$('#{$fileId}').val(value);
			} else {
				showError(file.id, response.message);
			}
		} else {
			showError(file.id, '上传失败');
		}
	});
	uploader.on('uploadError', function( file ) {
		showError(file.id, '上传失败');
	});
	uploader.on('uploadComplete', function( file ) {
		$( '#'+file.id ).find('.progress').remove();
	});
});
</script>
EOT;
		return $html;
	}
}

class FormImage extends FormImages {
	public function renderInput() {
		$this->maxCount(1);
		return parent::renderInput();
	}
}

class FormFiles extends FormGroup {

	public $maxCount = 6;
	public $allowFiles = 'doc,docx,xls,xlsx,ppt,pptx,txt,rar,zip,7z,html,htm,mp3,mov,mp4,avi';

	public function maxCount($maxCount) {
		$this->maxCount = $maxCount;
	}

	public function allowFiles($allowFiles) {
		$this->allowFiles = $allowFiles;
	}

	public function renderInput() {
		$fileId = 'i_'.$this->key;
		$html = "<input type=\"hidden\" id=\"{$fileId}\" name=\"n_{$this->key}\" value=\"{$this->value}\" /> \n";
		$fileListId = 'fileList_'.$this->key;
		$filePickerId = 'filePicker_'.$this->key;
		$files = empty($this->value) ? [] : explode(';', $this->value);
		$html .= '<div class="webuploader clearfix files-with-name">'."\n";
		$html .= '<div id="'.$fileListId.'" class="uploader-list">'."\n";
		foreach ($files as $file) {
			list($key, $name) = explode(',', $file, 2);
			$ext = substr(strrchr($name, '.'), 1); 
			$html .= '<div class="file-item"><span class="file-icon file-icon-'.$ext.'"></span><span class="file-name">'.$name.'</span><i class="fa fa-close"></i></div>'."\n";
		}
		$html .= '</div>'."\n";
		$html .= '<div id="'.$filePickerId.'" class="file-upload-btn"><i class="fa fa-plus fa-3x"></i><br />上传文件</div>'."\n";
		$html .= '</div>'."\n";
		$html .= <<<EOT
<script type="text/javascript">
$('body').ready(function(){
	var maxCount = {$this->maxCount};
	var uploader = WebUploader.create({
		auto: true,
		server: '/attachment/upload?dir=auto',
		pick: '#{$filePickerId}',
		accept: {
			title: '上传文件',
			extensions: '{$this->allowFiles}',
			//mimeTypes: 'image/*'
		}
	});
	function checkCount() {
		var items = $('#{$fileListId} .file-item');
		$('#{$filePickerId}').toggle(items.length < maxCount);
	}
	checkCount();
	function removeItem(btn) {
		var item = $(btn).closest('div');
		var index = $('#{$fileListId} .file-item').index(item);
		item.remove();
		var value = $('#{$fileId}').val().split(';');
		value.splice(index, 1);
		$('#{$fileId}').val(value.join(';'));
		checkCount();
	}
	$('#{$fileListId} .file-item i').click(function(){
		removeItem(this);
	});
	uploader.on('fileQueued', function( file ) {
		var li = $(
				'<div id="' + file.id + '" class="file-item">' +
					'<span class="file-icon file-icon-' + file.ext + '"></span>' +
					'<span class="file-name">' + file.name + '</span>' +
					'<i class="fa fa-close"></i>' +
				'</div>'
				),
			img = li.find('img');
		$('#{$fileListId}').append(li);
		checkCount();
		li.find('i').click(function(){
			removeItem(this);
		});
	});
	uploader.on('uploadProgress', function( file, percentage ) {
		var li = $( '#'+file.id ), percentDiv = li.find('.progress span');
		if ( !percentDiv.length ) {
			percentDiv = $('<p class="progress"><span></span></p>').appendTo( li ).find('span');
		}
		percentDiv.css('width', percentage * 100 + '%' );
	});
	function showError(fileId, msg) {
		console.log(fileId);
		var li = $('#'+fileId), errorDiv = li.find('p.error');
		if ( !errorDiv.length ) {
			errorDiv = $('<p class="error"></p>').appendTo( li );
		}
		errorDiv.text(msg);
	}
	uploader.on('uploadSuccess', function( file, response ) {
		//$( '#'+file.id ).addClass('upload-state-done');
		if (typeof response == 'object') {
			if (response.error == 0) {
				var value = $('#{$fileId}').val();
				var item = response.url + ',' + file.name;
				if (value != '') {
					value = value + ';' + item;
				} else {
					value = item;
				}
				$('#{$fileId}').val(value);
			} else {
				showError(file.id, response.message);
			}
		} else {
			showError(file.id, '上传失败');
		}
	});
	uploader.on('uploadError', function( file ) {
		showError(file.id, '上传失败');
	});
	uploader.on('uploadComplete', function( file ) {
		$( '#'+file.id ).find('.progress').remove();
	});
});
</script>
EOT;
		return $html;
	}
}

class FormFile extends FormFiles {
	public function renderInput() {
		$this->maxCount(1);
		return parent::renderInput();
	}
}

class FormValueStyle extends FormGroup {
	public $classStyle = 'input-xlarge';
	public $styles = [];

	public function mini() {
		$this->classStyle = 'input-small';
		return $this;
	}

	public function small() {
		$this->classStyle = 'input-small';
		return $this;
	}

	public function medium() {
		$this->classStyle = 'input-medium';
		return $this;
	}

	public function large() {
		$this->classStyle = 'input-large';
		return $this;
	}

	public function xLarge() {
		$this->classStyle = 'input-xlarge';
		return $this;
	}

	public function xxLarge() {
		$this->classStyle = 'input-xxlarge';
		return $this;
	}

	public function getStyle() {
		$style = '';
		if (!empty($this->styles)) {
			$style = ' style="';
			foreach ($this->styles as $k => $v) {
				$style .= "$k:$v;";
			}
			$style .= '"';
		}
		return $style;
	}

	public function width($width) {
		$this->styles['width'] = $width + 'px';
		return $this;
	}

	public function height($height) {
		$this->styles['height'] = $height + 'px';
		return $this;
	}
	public function style($name, $value) {
		$this->styles[$name] = $value;
		return $this;
	}
}

class FormText extends FormValueStyle {
	public $placeholder = '';

	public function placeholder($placeholder) {
		$this->placeholder = $placeholder;
		return $this;
	}

	public function renderInput() {
		$style = $this->getStyle();
		$readonly = $this->readonly ? ' readonly="readonly"' : '';
		return "<input id=\"i_{$this->key}\" type=\"text\" name=\"n_{$this->key}\" placeholder=\"{$this->placeholder}\" value=\"{$this->value}\" class=\"{$this->classStyle}\"{$style}{$readonly} /> \n";
	}



}

class FormTextarea extends FormText {
	public function renderInput() {
		$style = $this->getStyle();
		$readonly = $this->readonly ? ' readonly="readonly"' : '';
		return "<textarea id=\"i_{$this->key}\" type=\"text\" name=\"n_{$this->key}\" placeholder=\"{$this->placeholder}\"  class=\"{$this->classStyle}\"{$style}{$readonly}>{$this->value}</textarea> \n";
	}
}

class FormRichText extends FormTextarea {
	public function renderInput() {
		if (!isset($this->styles['width'])) {
			$this->style('width', '100%');
		}
		if (!isset($this->styles['height'])) {
			$this->style('height', '500px');
		}
		$html = parent::renderInput();
		$html .= <<<EOT
<script type="text/javascript">
var editor_{$this->key};
$('body').ready(function() {
	KindEditor.ready(function(K) {
		editor_{$this->key} = K.create('#i_{$this->key}', {
			allowFileManager : false
		});
	});
});
</script>
EOT;
		return $html;
	}
}

class FormSelect extends FormValueStyle {
	public $options = [];
	public function __construct($key='', $options=[]) {
		$this->key($key);
		$this->options($options);
	}

	public function options($options) {
		if (is_array($options)) {
			foreach ($options as $ok => $ov) {
				if (is_array($ov) && isset($ov['value'])) {
					//fixed
					if (isset($ov['key'])) {
						$ov['name'] = $ov['key'];
					}
					if (isset($ov['name'])) {
						$this->options[] = [
							'name' => $ov['name'],
							'value' => $ov['value']
						];
					}
				} else if (is_string($ov)) {
					$this->options[] = [
						'name' => $ov,
						'value' => $ok.''
					];
				}
			}
		}
		return $this;
	}

	public function renderInput() {
		$style = $this->getStyle();
		$readonly = $this->readonly ? ' readonly="readonly"' : '';
		$html = "<select id=\"i_{$this->key}\" name=\"n_{$this->key}\" class=\"{$this->classStyle}\"{$style}{$readonly}> \n";
		foreach ($this->options as $option) {
			$selected = $this->value == $option['value'] ? ' selected="selected"' : '';
			$html .= "<option value=\"{$option['value']}\"{$selected}>{$option['name']}</option>\n";
		}
		$html .= "</select> \n";
		return $html;
	}
}