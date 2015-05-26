<?php namespace Cmsable\Model;

class EloquentResourceManager
{

    public function findOrFail($id);

    // Return new Model (CREATE)
    // Momentan:
    // makeNode,
    // fire('sitetree.create') // Nicht in Dispatcher
    // fire("sitetree.$pageTypeId.form-created", [$this->form, $page]); -> stösst listen auf form events an
    // $form->fillByArray($page->toArray()); 
    // fire("sitetree.$pageTypeId.form-filled", [$this->form, $page]); -> plugin->fillForm
    //
    // public function makeCreateForm(array $attributes=[])
    // (Würde funktionieren, wenn form eine getModel/setModel Methode hätte)
    public function make(array $attributes=[]);

    // Actually create the model
    // Momentan:
    // makeNode (!parent_id)
    // $page->fill($this->form->getData())
    // fire("sitetree.store", [$page]); // Nicht in Dispatcher
    // fire("sitetree.$pageTypeId.creating", [$this->form, $page]); // Nicht in Dispatcher
    // $model->makeChildOf($page, $parent);
    // fire("sitetree.$pageTypeId.created", [$this->form, $page]); // Nicht in Dispatcher
    //
    // (würde funktionieren, form müsste intern behandelt werden und die Methode
    // müsste das Model zurückgeben
    public function store(array $attributes=[]);


    // Retrieve a model to edit
    // $page = get()
    // fire("sitetree.edit", [$page]); -> stösst loadPlugin an
    // fire("sitetree.$pageTypeId.form-created", [$this->form, $page]) -> stösst listen auf form events an
    // $form->fillByArray($page->toArray());
    // fire("sitetree.$pageTypeId.form-filled", [$this->form, $page]); -> plugin->fillForm
    //
    // public function editByForm(Model $model)
    // (funktioniert, wenn das eine Form zurückgibt)
    public function edit(Model $model);

    // Store a modified model
    // $page = get()
    // fire("sitetree.update", [$page]); -> stööst loadPlugin an
    // fire("sitetree.$pageTypeId.form-created", [$this->form, $page]); -> stösst listen auf form events an
    // $page->fill($this->form->getData(FALSE));
    // ($pageType.changed) fire("sitetree.page-type-leaving", [$page, $oldPageTypeId]); -> plugin->processPageTypeLeave
    // fire("sitetree.$pageTypeId.updating", [$this->form, $page]); -> prepareSave
    // $model->saveNode($page);
    // fire("sitetree.$pageTypeId.updated", [$this->form, $page]); ->finalizeSave
    //
    // Funktioniert auch, form muss intern behandelt werden
    public function update(Model $model, array $attributes);

    // $page = get()
    // fire("sitetree.destroy", [$page]);
    // fire("sitetree.$pageTypeId.destroying", [$page]);
    // $model->delete($page);
    // fire("sitetree.$pageTypeId.destroyed", [$page]);
    //
    // funktioniert sowieso
    public function delete();

}