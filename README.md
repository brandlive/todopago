<a name="inicio"></a>
plugin-magento
============

Plug in para la integración con gateway de pago <strong>Todo Pago</strong>
- [Consideraciones Generales](#consideracionesgenerales)
- [Instalación](#instalacion)
- [Configuración](#configuracion)
 - [Configuración plug in](#confplugin)
- [Datos adiccionales para prevención de fraude](#cybersource) 
- [Consulta de transacciones](#constrans)
- [Tablas de referencia](#tablas)

<a name="consideracionesgenerales"></a>
## Consideraciones Generales
El plug in de pagos de <strong>Todo Pago</strong>, provee a las tiendas Magento de un nuevo m&eacute;todo de pago, integrando la tienda al gateway de pago.
La versión de este plug in esta testeada en PHP 5.3 en adelante y MAGENTO 1.7 a 1.9

<a name="instalacion"></a>
## Instalación
1. Descomprimir el archivo magento-plugin-master.zip. 
2.	Copiar carpeta 'app', 'js', 'skin' y 'lib' al root de magento con los mismos nombres.
3.	Ir a  System->Cache Managment y refrescar el cache.
4.	Luego ir a 'Setting->Configuration->Payment Methods' y configurar desde la pestaña de <strong>Todo Pago</strong>.

Observaci&oacute;n:
Descomentar: <em>extension=php_curl.dll</em>, <em>extension=php_soap.dll</em> y <em>extension=php_openssl.dll</em> del php.ini, ya que para la conexión al gateway se utiliza la clase <em>SoapClient</em> del API de PHP.
<br />
[<sub>Volver a inicio</sub>](#inicio)

<a name="configuracion"></a>
##Configuración

[configuración plug in](#confplugin).
<a name="confplugin"></a>
####Configuración plug in
Para llegar al menu de configuración ir a: <em>System->Configuration</em> y seleccionar Paymenth Methods en el menú izquierdo. Entre los medios de pago aparecerá una solapa con el nombre <strong>Todo Pago</strong>. El Plug-in esta separado en configuarción general y 3 sub-menues.<br />
<sub><em>Menú principal</em></sub>
![imagen de configuracion](https://raw.githubusercontent.com/TodoPago/imagenes/master/README.img/menu_todopago.PNG)
<sub><em>Menú ambiente</em></sub>
![imagen de configuracion](https://raw.githubusercontent.com/TodoPago/imagenes/master/README.img/configuracion_magento1.PNG)
<sub><em>Meenú estados y menú servicios</em></sub>
![imagen de configuracion](https://raw.githubusercontent.com/TodoPago/imagenes/master/README.img/configuracion_magento2.PNG)

<a name="confplanes"></a>
<br />

[<sub>Volver a inicio</sub>](#inicio)
<a name="tca"></a>
## Nuevas columnas y atributos
El plug in para lograr las nuevas funcionalidades y su persistencia dentro del framework crear&aacute; nuevas tablas, columnas y atributos:

#####Nuevas Columnas:
1. en tabla sales_flat_order: todopagoclave.

#####Nuevos atributos:
1. del tipo "catalog_product": todopagofechaevento, todopagocodigo, todopagoenvio, todopagoservicio, todopagodelivery.
2. del tipo "customer": celular. 
<br/>
[<sub>Volver a inicio</sub>](#inicio)

<a name="cybersource"></a>
## Prevención de Fraude
- [Consideraciones Generales](#cons_generales)
- [Consideraciones para vertical RETAIL](#cons_retail)

<a name="cons_generales"></a>
####Consideraciones Generales (para todas los verticales, por defecto RETAIL)
El plug in, toma valores est&aacute;ndar del framework para validar los datos del comprador. Principalmente se utiliza una instancia de la clase <em>Mage_Sales_Model_Order</em>.
Para acceder a los datos del comprador se utiliza el metodo getBillingAddress() que devuelve un objeto ya instanciado del cual se usan los siguientes m&eacute;todos:

```php
   $order = new Mage_Sales_Model_Order ();
   $order->load($id);
-- Ciudad de Facturación: $order->getBillingAddress()->getCity();
-- País de facturación: $order->getBillingAddress()->getCountry();
-- Identificador de Usuario: $order->getBillingAddress()->getCustomerId();
-- Email del usuario al que se le emite la factura: $order->getBillingAddress()->getEmail();
-- Nombre de usuario el que se le emite la factura: $order->getBillingAddress()->getFirstname();
-- Apellido del usuario al que se le emite la factura: $order->getBillingAddress()->getLastname();
-- Teléfono del usuario al que se le emite la factura: $order->getBillingAddress()->getTelephone();
-- Provincia de la dirección de facturación: $order->getBillingAddress()->getRegion();
-- Domicilio de facturación: $order->getBillingAddress()->getStreet1();
-- Complemento del domicilio. (piso, departamento): $order->getBillingAddress()->getStreet2();
-- Moneda: $order->getBaseCurrencyCode();
-- Total:  $order->getGrandTotal();
-- IP de la pc del comprador: $order->getRemoteIp();
```
Otros de los modelos utlilizados es <em>Customer</em> del cual a trav&eacute;s  del m&eacute;todo <em>getPasswordHash()</em>, se extrae el password del usuario (comprador) y la tabla <em>sales_flat_invoice_grid</em>, donde se consultan las transacciones facturadas al comprador. 
<a name="cons_retail"></a> 
####Consideraciones para vertical RETAIL
Las consideración para el caso de empresas del rubro <strong>RETAIL</strong> son similares a las <em>consideraciones generales</em> con la diferencia de se utiliza el m&eacute;todo getShippingAddress() que devuelve un objeto y del cual se utilizan los siguientes m&eacute;todos;
```php
-- Ciudad de envío de la orden: $order->getShippingAddress()->getCity();
-- País de envío de la orden: $order->getShippingAddress()->getCountry();
-- Mail del destinatario: $order->getShippingAddress()->getEmail();
-- Nombre del destinatario: $order->getShippingAddress()->getFirstname();
-- Apellido del destinatario: $order->getShippingAddress()->getLastname();
-- Número de teléfono del destinatario: $order->getShippingAddress()->getTelephone();
-- Código postal del domicio de envío: $order->getShippingAddress()->getPostcode();
-- Provincia de envío: $order->getShippingAddress()->getRegion();
-- Domicilio de envío: $order->getShippingAddress()->getStreet1();
-- Método de despacho: $order->getShippingDescription();
-- Código de cupón promocional: $order->getCuponCode();
-- Para todo lo referido productos: $order->getItemsCollection();
```
nota: el valor resultante de $order->getItemsCollection(), se usan como referencias para conseguir informaci&oacute;n del modelo catalog/producto - a través de los métodos <strong>getDescription(), getName(), getSku(), getQtyOrdered(), getPrice()</strong>-.

####Muy Importante
<strong>Provincias:</strong> uno de los datos requeridos para prevención común a todos los verticales  es el campo provinicia/state tanto del comprador como del lugar de envío, para tal fin el plug in utiliza el valor del campo región de las tablas de la orden (sales_flat_order_address) a través del getRegion() tanto del <em>billingAddress</em> como del <em>shippingAddress</em>. El formato de estos datos deben ser tal cual la tabla de referencia (tabla provincias). Para simplificar la implementación de la tabla en magento se deja para su implementación la clase Decidir\Decidirpago\Model\System\Config\Source\Csprovincias.php, con el formato requerido. Al final de este documento se muestra un script sql que pude ser tomado de refrencia.
<br />
<strong>Celular:</strong> el plug in crear&aacute; un nuevo atributo de customer del tipo EAV, con el nombre (Attribute Code) celular que se ver&aacute; en la vista con la etiqueta (label) Celular, el cual en deberá ser implemantado para su utilizaci&oacute;n.
Ejemplo:
```php
$customer = Mage::getModel('customer/customer')->load($customer_id);
$customer->getCelular();
```
En caso que la tienda decida no implementar este nuevo atributo o que el valor quede vac&iacute;o el plug in mandara al sistema el mismo n&uacute;mero que devuleve el m&eacute;todo $order->getBillingAddress()->getTelephone(). 
####Nuevos Atributos en los productos
Para efectivizar la prevenci&oacute;n de fraude se han creado nuevos atributos de producto dentro de la categoria <em>"Prevenci&oacute;n de Fraude"</em>. 
![imagen nuevos catalogo producto](https://raw.githubusercontent.com/TodoPago/imagenes/master/README.img/catalogo_producto.PNG)<br />
<sub></sub><br />
![imagen campos cybersource](https://raw.githubusercontent.com/TodoPago/imagenes/master/README.img/campos_prevenciondefraude.PNG)
<sub>Estos campos no son obligatorios aunque si requeridos para Control de Fraude</sub>
<br />
[<sub>Volver a inicio</sub>](#inicio)

<a name="constrans"></a>
## Consulta de Transacciones
El plug in crea un nuevo <strong>tab</strong> para poder consultar <strong>on line</strong> las características de la transacci&oacute;n en el sistema de Todo Pago.
![imagen consulta de trnasacciones](https://raw.githubusercontent.com/TodoPago/imagenes/master/README.img/getstatus.PNG)
<br />
[<sub>Volver a inicio</sub>](#inicio)

<a name="tablas"></a>
## Tablas de Referencia
######[Provincias](#p)
######[Script SQL provincias](#sp)

<a name="p"></a>
<p>Provincias</p>
<table>
<tr><th>Provincia</th><th>Código</th></tr>
<tr><td>CABA</td><td>C</td></tr>
<tr><td>Buenos Aires</td><td>B</td></tr>
<tr><td>Catamarca</td><td>K</td></tr>
<tr><td>Chaco</td><td>H</td></tr>
<tr><td>Chubut</td><td>U</td></tr>
<tr><td>Córdoba</td><td>X</td></tr>
<tr><td>Corrientes</td><td>W</td></tr>
<tr><td>Entre Ríos</td><td>E</td></tr>
<tr><td>Formosa</td><td>P</td></tr>
<tr><td>Jujuy</td><td>Y</td></tr>
<tr><td>La Pampa</td><td>L</td></tr>
<tr><td>La Rioja</td><td>F</td></tr>
<tr><td>Mendoza</td><td>M</td></tr>
<tr><td>Misiones</td><td>N</td></tr>
<tr><td>Neuquén</td><td>Q</td></tr>
<tr><td>Río Negro</td><td>R</td></tr>
<tr><td>Salta</td><td>A</td></tr>
<tr><td>San Juan</td><td>J</td></tr>
<tr><td>San Luis</td><td>D</td></tr>
<tr><td>Santa Cruz</td><td>Z</td></tr>
<tr><td>Santa Fe</td><td>S</td></tr>
<tr><td>Santiago del Estero</td><td>G</td></tr>
<tr><td>Tierra del Fuego</td><td>V</td></tr>
<tr><td>Tucumán</td><td>T</td></tr>
</table>
[<sub>Volver a inicio</sub>](#inicio)

<a name="sp"></a>
```sql
INSERT INTO
directory_country_region (region_id, country_id , code, default_name)
VALUES
('4859', 'AR', 'C', 'CABA'),
('486' , 'AR', 'B', 'Buenos Aires'),
('487' , 'AR', 'A', 'Salta'),
('488' , 'AR', 'K', 'Catamarca'),
('489' , 'AR', 'H', 'Chaco'),
('490' , 'AR', 'U', 'Chubut'),
('491' , 'AR', 'X', 'Cordoba'),
('492' , 'AR', 'W', 'Corrientes'),
('493' , 'AR', 'E', 'Entre Rios'),
('494' , 'AR', 'P', 'Formosa'),
('495' , 'AR', 'Y', 'Jujuy'),
('496' , 'AR', 'L', 'La Pampa'),
('497' , 'AR', 'F', 'La Rioja'),
('498' , 'AR', 'M', 'Mendoza'),
('499' , 'AR', 'N', 'Misiones'),
('500' , 'AR', 'Q', 'Neuquen'),
('501' , 'AR', 'R', 'Rio Negro'),
('502' , 'AR', 'J', 'San Juan'),
('503' , 'AR', 'D', 'San Luis'),
('504' , 'AR', 'S', 'Santa Fe'),
('505' , 'AR', 'G', 'Santiago del Estero'),
('506' , 'AR', 'V', 'Tierra del Fuego'),
('507' , 'AR', 'T', 'Tucuman'),
('5071', 'AR', 'Z', 'Santa Cruz');

INSERT INTO
directory_country_region_name (locale, region_id , name)
VALUES
('en_US', '4859', 'CABA'),
('en_US', '486', 'Buenos Aires'),
('en_US', '487', 'Salta'),
('en_US', '488', 'Catamarca'),
('en_US', '489', 'Chaco'),
('en_US', '490', 'Chubut'),
('en_US', '491', 'Cordoba'),
('en_US', '492', 'Corrientes'),
('en_US', '493', 'Entre Rios'),
('en_US', '494', 'Formosa'),
('en_US', '495', 'Jujuy'),
('en_US', '496', 'La Pampa'),
('en_US', '497', 'La Rioja'),
('en_US', '498', 'Mendoza'),
('en_US', '499', 'Misiones'),
('en_US', '500', 'Neuquen'),
('en_US', '501', 'Rio Negro'),
('en_US', '502', 'San Juan'),
('en_US', '503', 'San Luis'),
('en_US', '504', 'Santa Fe'),
('en_US', '505', 'Santiago del Estero'),
('en_US', '506', 'Tierra del Fuego'),
('en_US', '507', 'Tucuman'),
('en_US','5071', 'Santa Cruz');
```

[<sub>Volver a inicio</sub>](#inicio)
