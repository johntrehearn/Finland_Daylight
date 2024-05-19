<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add("city", TextType::class, [
                "label" => "Enter a Finnish city",
                "attr" => ["class" => "form-control"],
                "required" => true,
            ])
            ->add("city2", TextType::class, [
                "label" => "Enter a Finnish city",
                "attr" => ["class" => "form-control"],
                "required" => true,
            ])
            ->add("submit", SubmitType::class, [
                "label" => "Show Daylight Comparison",
                "attr" => ["class" => "font-serif text-2xl font-bold mb-4 ml-9 ps-11 px-8 text-center text-blue-500 btn btn-primary py-2 px-4 border border-blue-700 rounded"],
            ]);
    }
}