# Sistema de Planillas

Sistema de gestión de planillas desarrollado en PHP para la administración de empleados, nómina, departamentos y control de vacaciones.

## Características Principales

- Gestión de empleados
- Procesamiento de planillas
- Administración de departamentos
- Control de vacaciones
- Gestión de usuarios
- Reportes y consultas

## Requisitos del Sistema

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Servidor web (Apache/Nginx)
- XAMPP (recomendado para desarrollo)

## Instalación

1. Clonar el repositorio:
```bash
git clone [URL_DEL_REPOSITORIO]
```

2. Configurar la base de datos:
   - Importar el archivo `db_schema.sql` en tu servidor MySQL
   - Configurar las credenciales de la base de datos en el archivo de configuración

3. Configurar el servidor web:
   - Asegurarse que el directorio del proyecto está en la carpeta htdocs de XAMPP
   - Verificar los permisos de los archivos

4. Acceder al sistema:
   - Abrir el navegador y dirigirse a `http://localhost/planilla`
   - Iniciar sesión con las credenciales por defecto

## Estructura del Proyecto

```
planilla/
├── pages/           # Páginas del sistema
├── *.php           # Scripts principales
├── *.sql           # Archivos de base de datos
└── README.md       # Este archivo
```

## Seguridad

- No incluir archivos de configuración con credenciales en el repositorio
- Mantener actualizado el sistema y sus dependencias
- Realizar copias de seguridad regularmente

## Contribución

1. Fork el proyecto
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

## Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## Contacto

[Tu información de contacto]