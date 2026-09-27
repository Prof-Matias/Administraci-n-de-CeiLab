# Sistema de Gestión

Sistema desarrollado como parte del proyecto de **Ingeniería de Software**, orientado a la gestión de usuarios, productos y operaciones del sistema.

## Descripción

Este repositorio contiene los archivos necesarios para la configuración y puesta en funcionamiento de la base de datos del proyecto.

La base de datos está desarrollada utilizando **MySQL** y se encuentra organizada en dos scripts principales:

* `schema.sql` → creación de la estructura de la base de datos.
* `seed.sql` → carga de datos iniciales y datos de prueba.

---

## Tecnologías

* **MySQL** — Sistema gestor de base de datos.
* **MySQL Workbench** — Herramienta recomendada para ejecutar y administrar los scripts.
* **SQL** — Lenguaje utilizado para la definición y manipulación de los datos.
* **Git / GitHub** — Control de versiones y almacenamiento del proyecto.

---

##  Estructura

```text
 proyecto
│
├── schema.sql
├── seed.sql
└── README.md
```

### `schema.sql`

Contiene la definición de la base de datos, incluyendo:

* Creación de la base de datos.
* Creación de tablas.
* Claves primarias.
* Claves foráneas.
* Relaciones entre tablas.
* Restricciones necesarias para garantizar la integridad de los datos.

### `seed.sql`

Contiene los datos iniciales necesarios para realizar pruebas del sistema.

Incluye sentencias `INSERT` para cargar registros en las diferentes tablas.

---

#  Instalación y configuración

## Requisitos

Antes de comenzar, es necesario tener instalado:

* [MySQL Server](https://dev.mysql.com/downloads/mysql/)
* [MySQL Workbench](https://dev.mysql.com/downloads/workbench/)

También se recomienda contar con Git para clonar el repositorio.

---

## 1. Clonar el repositorio

Desde una terminal:

```bash
git clone URL_DEL_REPOSITORIO (de tu equipo)
```

Ingresar posteriormente a la carpeta del proyecto:

```bash
cd Administraci-n-de-CeiLab (nombre del proyecto)
```

---

## 2. Crear la estructura de la base de datos

Abrir el archivo:

```text
schema.sql
```

en MySQL Workbench y ejecutar el script completo.

Este paso creará la base de datos, las tablas y sus respectivas relaciones.

---

## 3. Cargar los datos iniciales

Una vez ejecutado correctamente `schema.sql`, abrir:

```text
seed.sql
```

y ejecutar el script completo.

Este archivo se encargará de insertar los datos iniciales o de prueba.

> **Importante:** `seed.sql` debe ejecutarse después de `schema.sql`, ya que los datos se insertan sobre las tablas creadas por este último.

---

## Orden de ejecución

Los scripts deben ejecutarse en el siguiente orden:

```text
        schema.sql
            │
            ▼
   Crea la estructura
            │
            ▼
      Crea las tablas
            │
            ▼
         seed.sql
            │
            ▼
    Inserta los datos
```

---

# Seguridad

Las contraseñas almacenadas en la base de datos **no se guardan en texto plano**.

Se utiliza **bcrypt** para generar un hash de las contraseñas antes de almacenarlas.

Por ejemplo:

```text
Contraseña original
       ↓
     bcrypt
       ↓
Hash almacenado en la BD
```

Durante el inicio de sesión, el sistema puede utilizar `password_verify()` de PHP para comprobar la contraseña introducida por el usuario.

> **Nota:** Los hashes incluidos en `seed.sql` corresponden únicamente a usuarios de prueba y no deben utilizarse para almacenar contraseñas reales.

---

# Datos de prueba

El archivo `seed.sql` proporciona registros iniciales para facilitar las pruebas del sistema.

Estos datos permiten verificar el funcionamiento de las diferentes funcionalidades sin necesidad de ingresar manualmente todos los registros.

Si se modifica la estructura de la base de datos, también se deberán revisar los datos y sentencias `INSERT` correspondientes en `seed.sql`.

---

# Equipo de desarrollo

**Proyecto de Taller integrador de Sistemas**

* José Rodríguez
* Lucas Pereira
* Valentín Fernandéz
* Matías Indart

---

# Notas

* Ejecutar siempre `schema.sql` antes de `seed.sql`.
* Verificar que el servidor MySQL se encuentre activo.
* No utilizar contraseñas reales dentro de los archivos de prueba.
* Ante modificaciones en la estructura de la base de datos, actualizar los scripts correspondientes.

---

## Licencia

Este proyecto fue desarrollado con fines académicos exclusivos para la institución del Cerp del Este.
