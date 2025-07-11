<?php
namespace App\Classes\FormBuilder\Elements;

interface ElementInterface {
	const TYPE_TEXT = 'text';
	const TYPE_FILE = 'file';
	const TYPE_EMAIL = 'email';
	const TYPE_PASSWD = 'password';
	const TYPE_SEARCH = 'search';
	const TYPE_HIDDEN = 'hidden';
	const TYPE_URL = 'url';
	const TYPE_TEL = 'tel';
	const TYPE_NUMBER = 'number';
	const TYPE_RANGE = 'range';
	const TYPE_MONTH = 'month';
	const TYPE_WEEK = 'week';
	const TYPE_TIME = 'time';
	const TYPE_DATE = 'date';
	const TYPE_DATETIME = 'datetime';
	const TYPE_DATETIME_LOCAL = 'datetime-local';
	const TYPE_COLOR = 'color';
	const TYPE_CHECKBOX = 'checkbox';
	const TYPE_RADIO = 'radio';
	const TYPE_IMAGE = 'image';
	const TYPE_BUTTON = 'button';
	const TYPE_RESET = 'reset';
	const TYPE_SUBMIT = 'submit';
	
	const KEYTYPE_RSA = 'rsa';
	const KEYTYPE_DSA = 'dsa';
	const KEYTYPE_EC = 'ec';
	
	const ENCTYPE_APPLICATION = 'application/x-www-form-urlencoded';
	const ENCTYPE_MULTIPART = 'multipart/form-data';
	const ENCTYPE_TEXT = 'text/plain';
	
	const FORM_TARGET_BLANK = '_blank';
	const FORM_TARGET_SELF = '_self';
	const FORM_TARGET_PARENT = '_parent';
	const FORM_TARGET_TOP = '_top';
	
	const FORM_POST = 'post';
	const FORM_GET = 'get';
}
