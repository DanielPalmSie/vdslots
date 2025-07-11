<?php
namespace MVC;

class Form {
	private static $instance = null;
	public static $fileUploader_init = false;

	public static function getInstance() {
		return (self::$instance === null) ? self::$instance = new self : self::$instance;
	}

	public static function open(array $args) {
		$html = '<form';
		if (!empty($args)) {
			foreach ($args as $argc => $argv) {
				if (in_array($argc, array('action', 'method', 'id', 'class', 'enctype')) && !empty($argv)) {
					if ($argc === 'method' && ($argv !== 'post' || $argv !== 'get')) {
						$argv = 'post';
					}

					$html .= ' ' . $argc . '="' . $argv . '"';
				}
			}
		}
		return $html . '>';
	}

	public static function input(array $args) {
		$html = '<input';
		if (!empty($args)) {
			foreach ($args as $argc => $argv) {
				if (in_array($argc, array('disabled', 'checked', 'type', 'id', 'class', 'name', 'value', 'size')) && isset($argv)) {
					$html .= ' ' . $argc . '="' . $argv . '"';
				}
			}
		}
		return $html . '/>';
	}

	public static function labelInputFile($label = '', $id, $args = array(), $folder = null) {
		$html = (!is_null($folder)) ? '<input type="hidden" name="file_' . $id . '_target" value="' . $folder . '" />' : '';
		$html .= '<label for="file[' . $id . ']">' . $label . '</label><input type="file" name="file[' . $id . ']"';
		if (!empty($args)) {
			foreach ($args as $argc => $argv) {
				if (in_array($argc, array('checked', 'id', 'class', 'name', 'value', 'size')) && isset($argv)) {
					$html .= ' ' . $argc . '="' . $argv . '"';
				} else if ($argc == 'disabled') {
					$html .= ' disabled';
				}
			}
		}

		return $html . '/>';
	}

	public static function labelInput($label = '', $id, $args = array()) {
		$html = '<label for="' . $id . '">' . $label . '</label><input id="' . $id . '"';
		if (!empty($args)) {
			foreach ($args as $argc => $argv) {
				if (in_array($argc, array('checked', 'type', 'id', 'class', 'name', 'value', 'size')) && isset($argv)) {
					$html .= ' ' . $argc . '="' . $argv . '"';
				} else if ($argc == 'disabled') {
					$html .= ' disabled';
				}
			}
		}

		if (!in_array('name', $args)) {
			$html .= ' name="' . $id . '"';
		}

		return $html . '/>';
	}

	public static function labelSelectMultipleList($label = '', $id, $name, $size, $args = array(), $argi = null) {
		$html = '<label for="' . $id . '">' . $label . '</label>';
		$html .= '<select multiple name="' . $name . '" size="' . $size . '" id="' . $id . '">';
		$content = '';
		if (!empty($args)) {
			foreach ($args as $argc => $argv) {
				$html .= '<option value="' . $argc . '"';
				if (!is_null($argi) && $argi == $argc) {
					$html .= ' selected';
				}

				$html .= '>' . $argv . '</option>';
				continue;
			}
		}
		return $html . $content . '</select>';
	}

	public static function labelSelectList($label = '', $id, $args = array(), $argi = null, $name = null) {
		$name = (is_null($name)) ? $id : $name;
		$html = '<label for="' . $id . '">' . $label . '</label>';
		$html .= '<select name="' . $name . '" id="' . $id . '">';
		$content = '';
		if (!empty($args)) {
			foreach ($args as $argc => $argv) {
				$html .= '<option value="' . $argc . '"';
				if (!is_null($argi) && $argi == $argc) {
					$html .= ' selected';
				}

				$html .= '>' . $argv . '</option>';
				continue;
			}
		}
		return $html . $content . '</select>';
	}

	public static function selectList($name, array $args, $argi = null) {
		$html = '<select name="' . $name . '" id="' . $name . '">';
		$content = '';
		if (!empty($args)) {
			foreach ($args as $argc => $argv) {
				$html .= '<option value="' . $argc . '"';
				if (!is_null($argi) && $argi == $argc) {
					$html .= ' selected';
				}

				$html .= '>' . $argv . '</option>';
				continue;
			}
		}
		return $html . $content . '</select>';
	}

	public static function textarea(array $args) {
		$html = '<textarea';
		$content = '';
		if (!empty($args)) {
			foreach ($args as $argc => $argv) {
				if (in_array($argc, array('rows', 'cols', 'id', 'class', 'name', 'value')) && !empty($argv)) {
					if ($argc === 'value') {
						$content = $argv;
						continue;
					}
					$html .= ' ' . $argc . '="' . $argv . '"';
				}
			}
		}
		return $html . '>' . $content . '</textarea>';
	}

	public static function close() {return '</form>';}

}
