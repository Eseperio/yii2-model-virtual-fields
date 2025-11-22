# project context

The goal is to create a Yii2 extension named **yii2-model-virtual-fields**.
This library allows any application to add **virtual (dynamic) fields** to existing ActiveRecord models **without modifying** their database tables.

### purpose

The library enables developers to define new fields for any entity at runtime. These virtual fields:

* have a name, datatype, and validation rules,
* are stored in dedicated tables managed by the extension,
* are exposed on the model as **native properties** (`$model->virtualFieldName`),
* automatically integrate with:

  * ActiveForm,
  * DetailView,
  * GridView,
  * model validation,
  * model persistence.

### design overview

1. **Internal DB tables** store field definitions and values.
2. A **Yii2 module** with a required property `entityMap` maps integer entity-type identifiers to FQNs of real models.
   The extension internally uses **integer IDs** to identify entity types.
3. A **behavior** (`VirtualFieldsBehavior`) attaches to any ActiveRecord and exposes virtual fields through `__get` and `__set`.
4. A **central service** (`VirtualFieldService`) handles definition loading, value retrieval, validation, casting, and persistence.
5. **Render helpers** support automatic UI integration in forms, DetailView, and GridView.
6. **Collision-safe field naming** ensures virtual fields never conflict with real attributes or getters.
7. A full **Codeception functional test suite**, using a minimal Yii2 application under `/tests/_app`.

The extension must be installable via Composer, fully decoupled, and usable by any Yii2 developer.

---

# development plan

## *yii2-model-virtual-fields*

---

## 1. project structure

* [ ] Create base structure for a Yii2 extension.
* [ ] Set package name: `yii2-model-virtual-fields`.
* [ ] Create `composer.json` with:

  * type `"yii2-extension"`,
  * PSR-4 autoloading,
  * minimal Yii2 dependencies,
  * `"serve"` script to start internal test app.
* [ ] Create directory layout:

  * `/src`, `/src/behaviors`, `/src/services`, `/src/models`, `/src/helpers`, `/src/exceptions`,
  * `/config`, `/migrations`,
  * `/tests`, `/tests/_app`,
  * `/docs`,
  * `README.md`.

---

## 2. main module

* [ ] Implement Yii2 module named `virtualFields`.
* [ ] Add a required configurable property `entityMap` (int → FQN).
* [ ] Validate during initialization:

  * keys are integers,
  * values are existing classes extending ActiveRecord.
* [ ] Document in README how entity-type integers are used and how to configure `entityMap`.

---

## 3. database layer and internal models

### tables

* [ ] Create migration for **virtual_field_definition** with:

  * `id`, `entity_type`, `name`, `label`, `data_type`,
  * `required`, `multiple`, `options`, `default_value`,
  * `active`, timestamps.
* [ ] Create migration for **virtual_field_value** with:

  * `id`, `definition_id`, `entity_type`, `entity_id`,
  * `value`, timestamps.
* [ ] Add optimal indexes.

### ActiveRecord models

* [ ] Create `VirtualFieldDefinition`.
* [ ] Create `VirtualFieldValue`.
* [ ] Add internal validation rules.

---

## 4. virtual field name validation

* [ ] Implement validator that checks a virtual field name against:

  * existing DB columns on the target model,
  * existing getters/setters,
  * existing public/protected properties,
  * any reserved or unsafe names,
  * allowed naming pattern.
* [ ] Integrate this validator when creating a new definition.

---

## 5. dynamic behavior

* [ ] Implement `VirtualFieldsBehavior`.
* [ ] Behavior must:

  * obtain entity type from model via `getObjectType()`,
  * load field definitions and values using `VirtualFieldService`,
  * expose virtual fields via `canGetProperty`, `canSetProperty`, `__get`, `__set`,
  * inject virtual validation rules in `beforeValidate`,
  * save virtual values in `afterInsert` and `afterUpdate`,
  * delete virtual values in `afterDelete`,
  * support mass assignment via `load()`.

---

## 6. central service

* [ ] Implement `VirtualFieldService` responsible for:

  * fetching definitions by entity type (with caching),
  * fetching and setting values per model,
  * validating values according to datatype,
  * casting between PHP and DB types,
  * deleting related values when model is removed,
  * managing internally registered datatypes.
* [ ] Register base types: string, int, float, bool, date, datetime, json, etc.

---

## 7. view integration

### forms

* [ ] Implement `VirtualFieldRenderer` to render form inputs according to field definitions.

### detailview

* [ ] Implement helper that transforms definitions into DetailView-compatible attribute arrays.

### gridview

* [ ] Implement helper that generates GridView columns for virtual fields:

  * value retrieval using the virtual property,
  * optional filtering support.

---

## 8. optional management ui

* [ ] Add optional CRUD for managing VirtualFieldDefinition records.
* [ ] Keep optional components fully decoupled from the core.

---

## 9. documentation

* [ ] Write README describing:

  * the purpose and architecture of the extension,
  * how integers represent entity types,
  * how to configure the module and `entityMap`,
  * how to create virtual fields,
  * how to use virtual fields as native properties,
  * integration in forms, DetailView, GridView,
  * best practices and naming collision warnings.
* [ ] Add conceptual diagrams.

---

## 10. tests and testing environment

### internal test application

* [ ] Build `/tests/_app` as a minimal Yii2 application:

  * sqlite database,
  * module configured,
  * example models.

### codeception

* [ ] Configure unit, functional, and optional acceptance suites.
* [ ] Add fixtures for definitions, values, and sample models.
* [ ] Implement tests covering:

  * definition creation,
  * name collision rejection,
  * value assignment and validation,
  * property access through behavior,
  * saving and deleting values,
  * form/view/grid integration,
  * basic performance consistency.

---

## 11. testing automation

* [ ] Add `"serve"` script to start test app.
* [ ] Document running:

  * `composer run serve`,
  * `vendor/bin/codecept run`.

---

## 12. package publishing

* [ ] Validate PSR-4 and autoloading.
* [ ] Ensure migrations can be published and executed.
* [ ] Publish package on Packagist.
* [ ] Test real Composer installation on a clean Yii2 project.

