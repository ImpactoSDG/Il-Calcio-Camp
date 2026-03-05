-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema impactos_Il_Calcio_Camp
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `impactos_Il_Calcio_Camp` DEFAULT CHARACTER SET utf8 ;
USE `impactos_Il_Calcio_Camp` ;

-- -----------------------------------------------------
-- Table `impactos_Il_Calcio_Camp`.`categoria_articulo`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `impactos_Il_Calcio_Camp`.`categoria_articulo` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `descripcion` VARCHAR(45) NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `impactos_Il_Calcio_Camp`.`articulo`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `impactos_Il_Calcio_Camp`.`articulo` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(255) NULL,
  `precio_actual` DECIMAL(10,2) NULL,
  `costo_actual` DECIMAL(10,2) NULL COMMENT 'redundante',
  `cod_barra` VARCHAR(1000) NULL,
  `id_categoria_articulo` INT NULL,
  `activo` TINYINT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_articulo_categoria_articulo1_idx` (`id_categoria_articulo` ASC) VISIBLE,
  CONSTRAINT `fk_articulo_categoria_articulo1`
    FOREIGN KEY (`id_categoria_articulo`)
    REFERENCES `impactos_Il_Calcio_Camp`.`categoria_articulo` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `impactos_Il_Calcio_Camp`.`ingreso_articulo`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `impactos_Il_Calcio_Camp`.`ingreso_articulo` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fecha_ingreso` DATE NULL,
  `vencimiento` DATE NULL,
  `es_ajuste` TINYINT NULL COMMENT 'ajustes positivos',
  `cantidad` DECIMAL(10,2) NULL,
  `id_articulo` INT NOT NULL,
  `precio_unitario` DECIMAL(10,2) NULL,
  `total` DECIMAL(10,2) NULL,
  `es_perecedero` TINYINT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_ingreso_articulo_articulo_idx` (`id_articulo` ASC) VISIBLE,
  CONSTRAINT `fk_ingreso_articulo_articulo`
    FOREIGN KEY (`id_articulo`)
    REFERENCES `impactos_Il_Calcio_Camp`.`articulo` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `impactos_Il_Calcio_Camp`.`estado_venta`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `impactos_Il_Calcio_Camp`.`estado_venta` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `descripcion` VARCHAR(45) NULL COMMENT 'Abierta; Cerrada; Otros.',
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `impactos_Il_Calcio_Camp`.`condicion_iva_receptor`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `impactos_Il_Calcio_Camp`.`condicion_iva_receptor` (
  `id` INT NOT NULL,
  `descripcion_condicion` VARCHAR(45) NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `impactos_Il_Calcio_Camp`.`provincia`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `impactos_Il_Calcio_Camp`.`provincia` (
  `id` INT NOT NULL,
  `provincia` VARCHAR(100) NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `impactos_Il_Calcio_Camp`.`cliente`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `impactos_Il_Calcio_Camp`.`cliente` (
  `id` INT NOT NULL,
  `nombre_cliente` VARCHAR(45) NULL,
  `condicion_iva` VARCHAR(45) NULL,
  `id_condicion_iva_receptor` INT NULL,
  `direccion` VARCHAR(45) NULL,
  `id_provinica` INT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_cliente_condicion_iva_receptor1_idx` (`id_condicion_iva_receptor` ASC) VISIBLE,
  INDEX `fk_cliente_provincia1_idx` (`id_provinica` ASC) VISIBLE,
  CONSTRAINT `fk_cliente_condicion_iva_receptor1`
    FOREIGN KEY (`id_condicion_iva_receptor`)
    REFERENCES `impactos_Il_Calcio_Camp`.`condicion_iva_receptor` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_cliente_provincia1`
    FOREIGN KEY (`id_provinica`)
    REFERENCES `impactos_Il_Calcio_Camp`.`provincia` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `impactos_Il_Calcio_Camp`.`venta`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `impactos_Il_Calcio_Camp`.`venta` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fecha` DATE NULL,
  `id_equipo` INT NULL,
  `descripcion_cliente` VARCHAR(255) NULL,
  `id_estado_venta` INT NOT NULL,
  `simbolo` VARCHAR(45) NOT NULL,
  `id_cliente` INT NULL,
  `tipo_vta` VARCHAR(45) NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_venta_estado_venta1_idx` (`id_estado_venta` ASC) VISIBLE,
  INDEX `fk_venta_cliente1_idx` (`id_cliente` ASC) VISIBLE,
  CONSTRAINT `fk_venta_estado_venta1`
    FOREIGN KEY (`id_estado_venta`)
    REFERENCES `impactos_Il_Calcio_Camp`.`estado_venta` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_venta_cliente1`
    FOREIGN KEY (`id_cliente`)
    REFERENCES `impactos_Il_Calcio_Camp`.`cliente` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `impactos_Il_Calcio_Camp`.`articulo_venta`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `impactos_Il_Calcio_Camp`.`articulo_venta` (
  `id_articulo_venta` INT NOT NULL AUTO_INCREMENT,
  `id_articulo` INT NOT NULL,
  `id_venta` INT NOT NULL,
  `cantidad` DOUBLE(10,2) NOT NULL,
  `precio_unitario` DOUBLE(10,2) NOT NULL,
  `total` DOUBLE(10,2) NOT NULL,
  PRIMARY KEY (`id_articulo_venta`),
  INDEX `fk_articulo_has_venta_venta1_idx` (`id_venta` ASC) VISIBLE,
  INDEX `fk_articulo_has_venta_articulo1_idx` (`id_articulo` ASC) VISIBLE,
  CONSTRAINT `fk_articulo_has_venta_articulo1`
    FOREIGN KEY (`id_articulo`)
    REFERENCES `impactos_Il_Calcio_Camp`.`articulo` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_articulo_has_venta_venta1`
    FOREIGN KEY (`id_venta`)
    REFERENCES `impactos_Il_Calcio_Camp`.`venta` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `impactos_Il_Calcio_Camp`.`medio_cobro`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `impactos_Il_Calcio_Camp`.`medio_cobro` (
  `id` INT NOT NULL,
  `descripcion` VARCHAR(255) NULL,
  `activo` TINYINT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `impactos_Il_Calcio_Camp`.`cobro`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `impactos_Il_Calcio_Camp`.`cobro` (
  `id` INT NOT NULL,
  `cliente_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_cobro_cliente1_idx` (`cliente_id` ASC) VISIBLE,
  CONSTRAINT `fk_cobro_cliente1`
    FOREIGN KEY (`cliente_id`)
    REFERENCES `impactos_Il_Calcio_Camp`.`cliente` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `impactos_Il_Calcio_Camp`.`venta_cobro`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `impactos_Il_Calcio_Camp`.`venta_cobro` (
  `id_venta_cobro` INT NOT NULL,
  `id_venta` INT NOT NULL,
  `id_cobro` INT NOT NULL,
  `id_medio_pago` INT NOT NULL,
  `monto` DOUBLE(10,2) NULL,
  PRIMARY KEY (`id_venta_cobro`),
  INDEX `fk_venta_has_cobro_cobro1_idx` (`id_cobro` ASC) VISIBLE,
  INDEX `fk_venta_has_cobro_venta1_idx` (`id_venta` ASC) VISIBLE,
  INDEX `fk_venta_cobro_medio_pago1_idx` (`id_medio_pago` ASC) VISIBLE,
  CONSTRAINT `fk_venta_has_cobro_venta1`
    FOREIGN KEY (`id_venta`)
    REFERENCES `impactos_Il_Calcio_Camp`.`venta` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_venta_has_cobro_cobro1`
    FOREIGN KEY (`id_cobro`)
    REFERENCES `impactos_Il_Calcio_Camp`.`cobro` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_venta_cobro_medio_pago1`
    FOREIGN KEY (`id_medio_pago`)
    REFERENCES `impactos_Il_Calcio_Camp`.`medio_cobro` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `impactos_Il_Calcio_Camp`.`equipo`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `impactos_Il_Calcio_Camp`.`equipo` (
  `id` INT NOT NULL,
  `activo` TINYINT NULL,
  `disciplina` VARCHAR(45) NULL,
  `nombre` VARCHAR(45) NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `impactos_Il_Calcio_Camp`.`cliente_equipo`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `impactos_Il_Calcio_Camp`.`cliente_equipo` (
  `id_cliente_equipo` INT NOT NULL,
  `id_cliente` INT NOT NULL,
  `id_equipo` INT NOT NULL,
  PRIMARY KEY (`id_cliente_equipo`),
  INDEX `fk_cliente_has_equipo_equipo1_idx` (`id_equipo` ASC) VISIBLE,
  INDEX `fk_cliente_has_equipo_cliente1_idx` (`id_cliente` ASC) VISIBLE,
  CONSTRAINT `fk_cliente_has_equipo_cliente1`
    FOREIGN KEY (`id_cliente`)
    REFERENCES `impactos_Il_Calcio_Camp`.`cliente` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_cliente_has_equipo_equipo1`
    FOREIGN KEY (`id_equipo`)
    REFERENCES `impactos_Il_Calcio_Camp`.`equipo` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `impactos_Il_Calcio_Camp`.`articulo_venta_ingreso_articulo`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `impactos_Il_Calcio_Camp`.`articulo_venta_ingreso_articulo` (
  `id_articulo_venta_ingreso_articulo` VARCHAR(45) NOT NULL,
  `articulo_venta_id_articulo_venta` INT NOT NULL,
  `ingreso_articulo_id` INT NOT NULL,
  `cantidad` VARCHAR(45) NULL,
  PRIMARY KEY (`id_articulo_venta_ingreso_articulo`),
  INDEX `fk_articulo_venta_has_ingreso_articulo_ingreso_articulo1_idx` (`ingreso_articulo_id` ASC) VISIBLE,
  INDEX `fk_articulo_venta_has_ingreso_articulo_articulo_venta1_idx` (`articulo_venta_id_articulo_venta` ASC) VISIBLE,
  CONSTRAINT `fk_articulo_venta_has_ingreso_articulo_articulo_venta1`
    FOREIGN KEY (`articulo_venta_id_articulo_venta`)
    REFERENCES `impactos_Il_Calcio_Camp`.`articulo_venta` (`id_articulo_venta`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_articulo_venta_has_ingreso_articulo_ingreso_articulo1`
    FOREIGN KEY (`ingreso_articulo_id`)
    REFERENCES `impactos_Il_Calcio_Camp`.`ingreso_articulo` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;