<?php
/**
 * A parent form class needs to be created to handle the forms
 * User: ricardo
 * Date: 2/28/17
 * Time: 9:13 AM
 */

namespace App\Forms;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use App\Classes\FormBuilder\FormBuilder;
use App\Classes\FormBuilder\Elements\ElementInterface;

class UserSearchForm
{
    public function userForm(Application $app, Request $request)
    {
        $oFormBuilder = new FormBuilder();
        // CREATE AN INPUT
        $oFormBuilder->createInput([
            'name' => 'firstname', // the name of this field which will be used as $_POST etc
            'type' => ElementInterface::TYPE_TEL, //=> default it uses input type "text"
            'value' => '', // the value to load as default in cases we edit an entry
            'label' => [ // label element: an array or string (if string it will use defaults)
                'text' => 'First name', // the text within the label element
                'wrap' => false, // wrap the label around the input field so the label attr "for" is omitted
                'after' => false, // do we want the label to be shown before or after the input field
                'attr' => ['class' => 'warning'] // additional attributes for the label element
            ], // or 'firstname'
            'comment' => [
                'template' => 'Snippets/my-comment.html',
                'text' => 'some comment' // a comment to be show after/ below an input field
            ],
            'attr' => [ // all attributes that will be added to the input element
                //'id' => 'hallllo', // if omitted the id attr will be added using the 'name' key from above
                //'class' => 'success',
                //'autocomplete' => true,
                'placeholder' => 'enter first name', // the placeholder for this field
                'autofocus' => true, // do we want to autofocus this element, should be only one per form
                //'disabled' => true, // disable this field
            ],
            'rules' => [ // all validation rules for this field
                'required' => false,
                'minlength' => 3, // max chars
                'maxlength' => 10, // max chars
                'max' => 32, // when a number is expected
                'min' => 3,
                'pattern' => 'alnum' // project search for _getHtmlPattern and add other patterns to the array in that method. Preferable do NOT use the attr array above to keep "validation rules" together
            ],
            //'template' => 'snippets/html/my-input.html' // has a default template located inside "/FormBuilder/Snippets/input.html". A path to an alternative template to be used where this input field should be wrapped into
        ]);

        // CREATE AN INPUT
        $oFormBuilder->createInput([
            'name' => 'firstname',
            //'type' => elements_ElementInterface::TYPE_TEL, => default it uses text
            'value' => '',
            'label' => [
                'text' => 'First name',
                'wrap' => false,
                'after' => false,
                'attr' => ['class' => 'warning']
            ], // or 'firstname'
            'comment' => 'some comment on this field',
            'attr' => [
                //'id' => 'hallllo',
                //'class' => 'success',
                //'autocomplete' => true,
                'autofocus' => true,
                //'disabled' => true,
            ],
            'rules' => [
                'required' => false,
                'minlength' => 3, // max chars
                'maxlength' => 10, // max chars
                'max' => 32, // when a number is expected
                'min' => 3,
                'pattern' => 'alnum'
            ],
            'template' => 'snippets/html/my-input.html'
        ]);

        // CREATE A TEXTAREA
        $oFormBuilder->createTextarea([
            'name' => 'comment',
            'value' => '',
            'label' => [
                'text' => 'My Comment',
                'wrap' => false,
                'after' => false,
                'attr' => ['class' => 'warning']
            ], // or 'firstname'
            'comment' => [
                'template' => 'snippets/html/my-comment.html',
                'text' => 'some comment'
            ],
            'attr' => [
                //'id' => 'hallllo',
                'class' => 'success',
                'autofocus' => true,
                'disabled' => false,
            ],
            'rules' => [
                'required' => true,
                'maxlength' => 10, // max chars
                'max' => 32, // when a number is expected
                'min' => 3
            ],
            'template' => 'snippets/html/my-input.html'
        ]);

        // CREATE A SELECT
        $oFormBuilder->createSelect([
            'name' => 'country',
            'value' => '2',
            'label' => [
                'text' => 'Country',
                'wrap' => false,
                'after' => false,
                'attr' => ['class' => 'warning']
            ], // or 'firstname'
            'options' => [
                ['value' => '', 'text' => 'Please select', 'attr' => []],
                ['value' => 1, 'text' => 'hello', 'attr' => ['disabled' => 'disabled']],
                ['value' => 2, 'text' => 'world'],
            ],
            'attr' => [
                //'id' => 'hallllo',
                'class' => 'success'
            ],
            'rules' => [
                'required' => true,
                'max' => 32,
                'min' => 3
            ],
            //'template' => 'snippets/html/select.html' => optional overrule
        ]);

        // CREATE A DATALIST
        $oFormBuilder->createDatalist([
            'name' => 'Animal',
            'value' => 'Duck',
            'label' => [
                'text' => 'Choose animal',
                'wrap' => false,
                'after' => false,
                'attr' => ['class' => 'warning']
            ], // or 'firstname'
            'options' => ['Asino', 'Cavallo', 'Farfalla', 'Pesce', 'Duck'],
            'attr' => [
                //'id' => 'hallllo',
                'class' => 'success'
            ],
            'rules' => [
                'required' => true,
                'max' => 32,
                'min' => 3
            ],
            //'template' => 'snippets/html/select.html' => optional overrule
        ]);

        // CREATE A KEYGEN
        $oFormBuilder->createKeygen([
            'name' => 'mykeygen',
            // 'type' => elements_ElementInterface::KEYTYPE_DSA, => default it uses elements_ElementInterface::KEYTYPE_RSA
            'label' => [
                'text' => 'Generate key',
                'wrap' => false,
                'after' => false,
                'attr' => ['class' => 'warning']
            ], // or 'firstname'
            'attr' => [
                //'id' => 'hallllo',
                'form' => 'someid'
            ],
            //'template' => 'snippets/html/select.html' => optional overrule
        ]);

        // CREATE A SINGLE CHECKBOX
        $oFormBuilder->createCheckbox([
            'name' => 'agree',
            'value' => '1',
            'label' => [
                'text' => 'Accept terms',
                'wrap' => true,
                'after' => true,
                'attr' => ['class' => 'warning']
            ],
            'attr' => [
                //'id' => 'hallllo',
                'class' => 'success'
            ],
            'rules' => [
                'required' => true,
                'max' => 32,
                'min' => 3
            ],
            //'template' => 'snippets/html/checkbox.html' => optional overrule
        ]);

        // CREATE MULTI RELATED CHECKBOXES
        $oFormBuilder->createCheckbox([[
            'name' => 'items[]',
            // 	'collection' => [
            // 		'order' => 1,
            // 		'name' => 'items',
            // 		//'template' => 'snippets/html/collection2.html'
            // 	],
            'value' => '1',
            'label' => [
                'text' => 'Item 1',
                'wrap' => true,
                'after' => true,
                'attr' => ['class' => 'warning']
            ],
            'attr' => [
                //'id' => 'hallllo',
                'class' => 'success'
            ],
            'rules' => [
                'required' => true,
                'max' => 32,
                'min' => 3
            ],
            //'template' => 'snippets/html/checkbox.html' => optional overrule
        ], [
            'name' => 'items[]',
            // 	'collection' => [
            // 		'order' => 1,
            // 		'name' => 'items',
            // 		//'template' => 'snippets/html/collection2.html'
            // 	],
            'value' => '2',
            'label' => [
                'text' => 'Item 2',
                'wrap' => true,
                'after' => true,
                'attr' => ['class' => 'warning']
            ],
            'attr' => [
                //'id' => 'hallllo',
                'class' => 'success'
            ],
            'rules' => [
                'required' => true,
                'max' => 32,
                'min' => 3
            ],
            //'template' => 'snippets/html/checkbox.html' => optional overrule
        ]]);

        // CREATE A RADIO
        $oFormBuilder->createRadio([
            'name' => 'gender',
            'value' => '1',
            'label' => [
                'text' => 'Gender',
                'wrap' => true,
                'after' => true,
                'attr' => ['class' => 'warning']
            ], // or 'firstname'
            'attr' => [
                //'id' => 'hallllo',
                'class' => 'success'
            ],
            'rules' => [
                'required' => true,
                'max' => 32,
                'min' => 3
            ],
            //'template' => 'snippets/html/radio10.html'
        ]);

        // CREATE A SUBMIT BUTTON USING INPUT TYPE SUBMIT
        $oFormBuilder->createInput([
            'name' => 'send',
            'type' => ElementInterface::TYPE_SUBMIT,
            'value' => 'This is an input submit',
            'attr' => [
                //'id' => 'hallllo',
                'class' => 'success',
                'checked' => true,
                'autofocus' => true,
                'disabled' => false,
            ],
            'rules' => [
                'required' => true
            ]
        ]);

        // CREATE A SUBMIT BUTTON USING BUTTON
        $oFormBuilder->createButton([
            'name' => 'send',
            'type' => ElementInterface::TYPE_SUBMIT,
            'value' => 'yeah',
            'text' => 'this is a button sumbit',
            'attr' => [
                //'id' => 'hallllo',
                'class' => 'success',
                'checked' => true,
                'autofocus' => true,
                'disabled' => false,
            ],
            'rules' => [
                'required' => false
            ]
        ]);


        if ($oFormBuilder->valid()) {
            //whatever
        }


        return $oFormBuilder->createForm([
            'template' => '',
            'notag' => false, // ommit key or set to true or false to surround with form tags or not
            'attr' => [
                'action' => '/',
                'target' => \App\Classes\FormBuilder\Elements\ElementInterface::FORM_TARGET_SELF,
                'enctype' => \App\Classes\FormBuilder\Elements\ElementInterface::ENCTYPE_MULTIPART,
                'method' => \App\Classes\FormBuilder\Elements\ElementInterface::FORM_POST
            ]
        ]);
    }
}