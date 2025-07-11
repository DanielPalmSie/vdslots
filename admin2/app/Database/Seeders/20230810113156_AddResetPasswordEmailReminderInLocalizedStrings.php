<?php

use App\Extensions\Database\Seeder\SeederTranslation;

class AddResetPasswordEmailReminderInLocalizedStrings extends SeederTranslation
{

    protected array $data = [

        'dgoj' => [
            'mail.password-reminder-change.subject' => '¿Qué tal si actualizas tu contraseña de Videoslots?',
            'mail.password-reminder-change.content' => '<p> Hola __FIRSTNAME__</p>
                               <p> &iquest;Creer&iacute;a que ha pasado m&aacute;s de un a&ntilde;o desde la &uacute;ltima vez que actualiz&oacute; su contrase&ntilde;a? Solo como un recordatorio amistoso, recomendamos cambiarlo una vez m&aacute;s para mantener su cuenta segura.</p>
                               <p> No hay prisa ni obligaci&oacute;n. Si se siente c&oacute;modo con su contrase&ntilde;a actual, puede conservarla. Pero si est&aacute; de humor para cambiar las cosas, puede hacerlo f&aacute;cilmente siguiendo los pasos a continuaci&oacute;n:</p>
                                <ul>
                                    <li>
                                        Inicie sesi&oacute;n en su cuenta.</li>
                                    <li>
                                        Ve a tu perfil.</li>
                                    <li>
                                        Haga clic en &quot;Editar contrase&ntilde;a&quot;.</li>
                                    <li>
                                        Despl&aacute;cese hacia abajo y agregue su nueva contrase&ntilde;a.</li>
                                </ul>
                                <p>Si tiene alguna pregunta o necesita ayuda, nuestro equipo de soporte 24/7 siempre est&aacute; aqu&iacute; para ayudarlo.</p>
                                <p>Puede contactarnos a trav&eacute;s del <a href="https://www.videoslots.es/customer-service/" target="_self"><span style="color: #0000ff;"><u>hat en vivot</u></span></a> o por correo electr&oacute;nico: <a href="mailto:es.support@videoslots.com" target="_self"><u>es.support@videoslots.com</u></a></p>
                                <p>Saludos cordiales,<br />
                                    Soporte<br />
                                    <a href="https://www.videoslots.es/" target="_self"><span style="color: #0000ff;"><u>Videoslots.es</u></span></a></p>
                                <p><img alt="" height="160" src="https://www.videoslots.com/file_uploads/video-slots.png" style="border: 0; margin: 0px;" width="220" /></p>',
        ]

    ];


}
