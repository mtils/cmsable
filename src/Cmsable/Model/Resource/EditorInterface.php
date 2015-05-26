<?php namespace Cmsable\Model\Resource;

/**
 * A resource editor is something like an presentation layer for php. It creates
 * and fills the forms.
 *
 **/
interface EditorInterface extends ManagerInterface
{

    /**
     * Create a model, the form, assign the model to the form and return it. If
     * validation failes or something similar throw an exception
     *
     * @param array $attributes
     * @return \FormObject\Form
     **/
    public function editNew(array $attributes=[]);

    /**
     * Create a form for model $model, assign the model to the form and return
     * the form
     *
     * @param mixed $id
     * @return \FormObject\Form
     **/
    public function edit($id);

    /**
     * Create a form without any side effects
     *
     * @param array $attributes
     * @return \FormObject\Form
     **/
    public function newForm(array $attributes=[]);

}