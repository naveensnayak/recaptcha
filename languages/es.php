<?php
/**
 * recaptcha language pack.
 */

$language = array(
	
	'recaptcha:public_key' => 'Introducir Clave Pública:',
    'recaptcha:private_key' => 'Introducir Clave Privada:',
    'recaptcha:use_recaptcha_registration' => 'Utilizar Recaptcha para el registro de usuarios/as.',
    'recaptcha:form_error' => 'Se requieren las Claves Pública y Privada',
    'recaptcha:settings_saved' => 'Configuración guardada correctamente',
    'recaptcha:label:human_verification' => 'Verificación humana:',
	'recaptcha:human_verification_failed' => 'Falló la verificación humana.<br>Introduce los valores correctos para el campo de verificación humana<br>
	    Puedes obtener una imagen diferente si haces clic en el botón de refrescar',
    'recaptcha:signup' => 'Regístrate en recaptcha para obtener tus claves pública y privada en %s',
    'recaptcha:verified' => 'Verificado',
);

add_translation("es", $language);
