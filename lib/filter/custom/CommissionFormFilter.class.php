<?php

/**
 * Course filter form.
 *
 * @package    conservatorio
 * @subpackage filter
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormFilterTemplate.php 11675 2008-09-19 15:21:38Z fabien $
 */
class CommissionFormFilter extends BaseCourseFormFilter
{
  public function configure()
  {
    unset($this['starts_at'], $this['quota'], $this['division_id'], $this['related_division_id']);

    $this->getWidget('name')->setOption('with_empty', false);
    $this->getWidgetSchema()->setHelp('name', 'Se buscara por nombre de comision o por nombre de materia.');
    $this->setWidget('year', new sfWidgetFormFilterInput(array('with_empty' => false)));
    $this->setValidator('year', new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))));

    $this->setWidget('school_year_id', new sfWidgetFormPropelChoice(array('model' => 'SchoolYear', 'add_empty' => false)));
    $this->setValidator('school_year_id', new sfValidatorPropelChoice(array('required' => false, 'model' => 'SchoolYear', 'column' => 'id')));

    $this->setWidget('current_period', new sfWidgetFormFilterInput(array('with_empty' => false)));
    $this->setValidator('current_period', new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))));

    $w = new sfWidgetFormChoice(array('choices' => array()));
    $this->setWidget('career_school_year', new dcWidgetAjaxDependence(array(
        'dependant_widget' => $w,
        'observe_widget_id' => 'course_filters_school_year_id',
        "message_with_no_value" => "Seleccione un año electivo y apareceran las carreras que correspondan",
        'get_observed_value_callback' => array(get_class($this), 'getYears')
      )));


    $this->setValidator('career_school_year', new sfValidatorPropelChoice(array('required' => false, 'model' => 'CareerSchoolYear', 'column' => 'id')));

    $this->setWidget('division', new sfWidgetFormPropelChoice(array('model' => 'division', 'add_empty' => true)));
    $this->setValidator('division', new sfValidatorPropelChoice(array('required' => false, 'model' => 'division', 'column' => 'id')));


    $this->setWidget('student', new dcWidgetFormPropelJQuerySearch(array('model' => 'Person', 'column' => array('lastname', 'firstname'), 'peer_method' => 'doSelectStudent')));
    $this->setValidator('student', new sfValidatorPropelChoice(array('required' => false, 'model' => 'Person', 'column' => 'id')));

    $this->getWidgetSchema()->setLabel('current_period', 'Período');
    $this->getWidgetSchema()->setLabel('career_school_year', 'Carrera');
    $this->getWidgetSchema()->setHelp('career_school_year', 'seleccione alguna de las carreras habilitadas');
  }

  public static function getYears($widget, $values)
  {
    $career_school_years = CareerSchoolYearPeer::retrieveBySchoolYear(null, SchoolYearPeer::retrieveByPK($values));

    $choices = array();
    foreach ($career_school_years as $career_school_year)
    {
      $choices[$career_school_year->getId()] = $career_school_year;
    }
    $widget->setOption('choices', $choices);

  }

  public function getFields()
  {
    return array_merge(parent::getFields(), array(
        'name' => 'Text',
        'year' => 'Number',
        'career_school_year' => 'ForeignKey',
        'current_period' => 'Number',
        'school_year_id' => 'ForeignKey',
        'division' => 'ForeignKey',
        'student' => 'ForeignKey'));

  }

  public function addCareerSchoolYearColumnCriteria($criteria, $field, $value)
  {
    if ($value !== null)
    {
      $criteria->addJoin(CoursePeer::ID, CourseSubjectPeer::COURSE_ID, Criteria::INNER_JOIN);
      $criteria->addJoin(CourseSubjectPeer::CAREER_SUBJECT_SCHOOL_YEAR_ID, CareerSubjectSchoolYearPeer::ID, Criteria::INNER_JOIN);
      $criteria->add(CareerSubjectSchoolYearPeer::CAREER_SCHOOL_YEAR_ID, $value);
    }
  }

  public function addStudentColumnCriteria(Criteria $criteria, $field, $value)
  {
    if ($value !== null)
    {
      $criteria->addJoin(StudentPeer::PERSON_ID, PersonPeer::ID);
      $criteria->addJoin(CourseSubjectStudentPeer::STUDENT_ID, StudentPeer::ID);
      $criteria->addJoin(CourseSubjectPeer::ID, CourseSubjectStudentPeer::COURSE_SUBJECT_ID);
      $criteria->addJoin(CoursePeer::ID, CourseSubjectPeer::COURSE_ID);
      $criteria->add(PersonPeer::ID,$value);
    }
  }

  public function addDivisionColumnCriteria(Criteria $criteria, $field, $value)
  {
    if ($value !== null)
    {
      #Recupero a todos los estudiantes de esa divicion
      $c = New Criteria();
      $c->add(DivisionPeer::ID, $value);
      $c->addJoin(DivisionPeer::ID, DivisionStudentPeer::DIVISION_ID);
      #Lo transformo en un arreglo de ids
      $c->clearSelectColumns();
      $c->addSelectColumn(DivisionStudentPeer::STUDENT_ID);
      $stm = DivisionStudentPeer::doSelectStmt($c);
      while ($row = $stm->fetch(PDO::FETCH_NUM))
        $students_id[] = $row[0];

      #Pregunto si existe algun alumno en esta division
      $criteria->addJoin(CoursePeer::ID, CourseSubjectPeer::COURSE_ID);
      $criteria->addJoin(CourseSubjectPeer::ID, CourseSubjectStudentPeer::COURSE_SUBJECT_ID);
      $criteria->add(CourseSubjectStudentPeer::STUDENT_ID, $students_id, Criteria::IN);
      #elimino lo repetidos
      $criteria->setDistinct(CoursePeer::ID);
    }
  }

  public function addYearColumnCriteria($criteria, $field, $value)
  {
    if ($value['text'] != '')
    {
      $criteria->addJoin(CoursePeer::ID, CourseSubjectPeer::COURSE_ID);
      $criteria->addJoin(CourseSubjectPeer::CAREER_SUBJECT_SCHOOL_YEAR_ID, CareerSubjectSchoolYearPeer::ID);
      $criteria->addJoin(CareerSubjectSchoolYearPeer::CAREER_SUBJECT_ID, CareerSubjectPeer::ID);
      $criteria->add(CareerSubjectPeer::YEAR, $value['text']);
    }
  }

  public function addNameColumnCriteria($criteria, $field, $value)
  {
    if ($value['text'] != '')
    {
      $value = $value['text'];
      $criteria->addJoin(CourseSubjectPeer::COURSE_ID, CoursePeer::ID);
      $criteria->addJoin(CourseSubjectPeer::CAREER_SUBJECT_SCHOOL_YEAR_ID, CareerSubjectSchoolYearPeer::ID);
      $criteria->addJoin(CareerSubjectSchoolYearPeer::CAREER_SUBJECT_ID, CareerSubjectPeer::ID);
      $criteria->addJoin(CareerSubjectPeer::SUBJECT_ID, SubjectPeer::ID, Criteria::INNER_JOIN);

      $criterion = $criteria->getNewCriterion(CoursePeer::NAME, "%$value%", Criteria::LIKE);
      $criterion->addOr($criteria->getNewCriterion(SubjectPeer::NAME, "%$value%", Criteria::LIKE));

      $criteria->add($criterion);
      $criteria->setDistinct();
    }
  }

  public function addSchoolYearIdColumnCriteria($criteria, $field, $value)
  {
    if ($value !== null)
    {
      $criteria->add(SchoolYearPeer::ID, $value);
      $criteria->addJoin(SchoolYearPeer::ID, CoursePeer::SCHOOL_YEAR_ID, Criteria::INNER_JOIN);
    }
  }
}