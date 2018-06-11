<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * Strings for component 'auth_emailadmin', language 'es', branch 'MOODLE_20_STABLE'
 * NOTE: Based on 'email' package by Martin Dougiamas
 *
 * @package   auth_emailadmin
 * @copyright 2012 onwards Felipe Carasso  {@link http://moodle.com}
 * Spanish translation by borekon {@link https://github.com/borekon}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['auth_emailadmindescription'] = '<p>Email-based self-registration with Admin confirmation permite a un usuario crear su porpia cuenta mediante el botón \'Nueva cuenta\' situado en la página de login. El administrador del sitio recibe entonces un mail con un enlace a una página donde confirmar la cuenta del nuevo usuario. Los login sucesivos del usuario se realizan de forma normal </p><p>Nota: Además de activar el plugin, hay que seleccionarlo en la lista desplegable situada en </i>Administración del sitio --> Extensiones --> Identificación --> Gestionar identificación --> Ajustes comunes</i>.</p>';
$string['auth_emailadminnoemail'] = 'Se intentó enviarte un mail pero ha fallado.';
$string['auth_emailadminrecaptcha'] = 'Añada un elemento de confirmación auditivo/visual a la página de registro. Esto previene el registro de spam y demás escoria. <br /><em>Se requiere la extensión PHP cURL.</em>';
$string['auth_emailadminrecaptcha_key'] = 'Activar reCAPTCHA';
$string['auth_emailadminsettings'] = 'Ajustes';
$string['auth_emailadminuserconfirmation'] = '
Hola {$a->firstname},
Bienvenid@ a la plataforma online del centro de formación Virgen de la Caridad ! Tu cuenta ha sido aprobada. 
A partir de ahora tendrás acceso a los cursos que te sean designados según la modalidad en la que te hayas matriculado.

* * *
¿Qué es el centro de formación Virgen de la Caridad?
Somos una empresa integrada  en el sector de la formación en el campo de las  Autoescuelas  y Formación en diversas áreas, 
como  Seguridad  vial, Manipulación de Carretillas elevadoras, Cursos de ADR en todas las especialidades, Prevención de Riesgos Laborales  y Operaciones en Planta Química.
Dando soporte  a Proyectos tecnológicos dentro de su área ejecutiva y de las Nuevas Tecnologías.

Dentro del Sector de Auto Escuela, Virgen de la Caridad Garantiza a los Alumnos, una excelencia en la enseñanza y en un ajustado calidad-tiempo para la obtención del carnet de conducir.
Las instalaciones del Centro de Formación Virgen de la Caridad tienen las más altas aplicaciones para interactuar alumno-centro.
Y con ello facilitar el auto aprendizaje on-line.
Además dentro del Centro están todos los servicios necesarios para facilitar el aprendizaje presencial de los alumnos.

Como Centro de Formación, En este punto se ha tomado en consideración elementos del ámbito técnico y comercial dentro del Sector 
y la Formación está orientada a las necesidades de dicho sector y facilitar a las Empresas a estar actualizadas en conocimientos 
sensibles en su área de negocio.

El Centro de Formación Virgen de la Caridad facilita el participar en sus cursos de formación a través de accesos al portal 
de Cursos en su página web,  gracias a una sencilla aplicación.

El Centro de Formación Virgen de la Caridad tiene un departamento de profesionales y docentes donde el servicio de dicha formación 
está avalada por Ingenieros del Sector muy altamente cualificados, garantizando los conocimientos específicos en las distintas materias.


Gracias,
El equipo de Virgen de la Caridad
---
<a href="http://virgendelacaridad.es/">http://virgendelacaridad.es/</a>
<a href="mailto:%66o%72%6da%63io%6e%40virg%65%6e%64%65%6c&#97;&#99;&#97;&#114;ida&#100;%2e%65s">Contacto</a>
<a href=tel:968533192>968533192</a>
';
$string['auth_emailadminconfirmation'] = '
Hola Admin,
A new account has been requested at \'{$a->sitename}\' with  the following data:
Any specific user field example:
user->lastname: {$a->lastname}
All custom fields:
{$a->customfields}
All user fields + custom fields:
{$a->userdata}
To confirm the new account, please go to this web address:
{$a->link}
In most mail programs, this should appear as a blue link which you can just click on.  If that doesn\'t work, then cut and paste the address into the address line at the top of your web browser window.
You can also confirm accounts from within Moodle by going to
Site Administration -> Users
';
$string['auth_emailadminconfirmationsubject'] = '{$a}: Confirmar cuenta';
$string['auth_emailadminconfirmsent'] = '<p>
Tu cuenta ha sido registrada y está pendiente de aprobación manual por parte de un administrador. Te llegará un correo con la confirmación de la cuenta en caso de ser legítima.</p>
';
$string['auth_emailadminnotif_failed'] = 'No se puede enviar la notificación de registro a: ';
$string['auth_emailadminnoadmin'] = 'No hay administradores a los que notificar. Comprueba la configuración de auth_emailadmin.';
$string['auth_emailadminnotif_strategy_key'] = 'Estrategia de notificación:';
$string['auth_emailadminnotif_strategy'] = 'Define la estrategia a seguir para enviar notificaciones de registro. Las opciones disponibles son "primer" administrador, "todos" los administradores o un administrador específico.';
$string['auth_emailadminnotif_strategy_first'] = 'Primer administrador';
$string['auth_emailadminnotif_strategy_all'] = 'Todos los administradores';
$string['pluginname'] = 'Email-based self-registration with admin confirmation';
