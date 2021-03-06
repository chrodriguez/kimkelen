<?php 
/*
 * Kimkëlen - School Management Software
 * Copyright (C) 2013 CeSPI - UNLP <desarrollo@cespi.unlp.edu.ar>
 *
 * This file is part of Kimkëlen.
 *
 * Kimkëlen is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License v2.0 as published by
 * the Free Software Foundation.
 *
 * Kimkëlen is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kimkëlen.  If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
 */ ?>
<?php

class StudentCareerSchoolYear extends BaseStudentCareerSchoolYear
{

  /**
   * Returns the array with CourseSubjecStudent.
   *
   * Posiciones del vector ['ANUAL']['QUATERLY']['BIMESTER'], no necesariamente se cargan todos..
   * El mismo vector, devuelve la cantidad de notas maximas en cada arreglo.
   * @return array
   */
  public function getCourses()
  {
    $css = CourseSubjectStudentPeer::retrieveByCareerSchoolYearAndStudent($this->getCareerSchoolYear(), $this->getStudent());
    $course_subject_per_type = array();
      $anual_max = 0;
      $quaterly_max = 0;
      $bimester_max = 0;
      $anual_period_max = 1;
      $quaterly_period_max = 1;
      $bimester_period_max = 1;
    foreach ($css as $course_subject_student)
    {
      $course_type = $course_subject_student->getCourseSubject()->getCareerSubjectSchoolYear()->getConfiguration()->getCourseType();
      $course_type;
      switch ($course_type)
      {
        case 1:
          $course_subject_per_type['ANUAL'][] = $course_subject_student;
          $course_subject_per_type['ANUAL']['marks'] = max($anual_max, $course_subject_student->countCourseSubjectStudentMarks());
          $course_subject_per_type['ANUAL']['periods'] = max($anual_period_max, $course_subject_student->countCourseSubjectStudentPeriods());
          $anual_max       =($anual_max > $course_subject_student->countCourseSubjectStudentMarks())?$anual_max:$course_subject_student->countCourseSubjectStudentMarks();
          $anual_period_max=($anual_period_max > $course_subject_student->countCourseSubjectStudentPeriods())?$anual_period_max:$course_subject_student->countCourseSubjectStudentPeriods();
          break;
        case 2:
          $course_subject_per_type['QUATERLY'][] = $course_subject_student;
          $course_subject_per_type['QUATERLY']['marks'] = max($quaterly_max, $course_subject_student->countCourseSubjectStudentMarks());
          $course_subject_per_type['QUATERLY']['periods'] = max($quaterly_period_max, $course_subject_student->countCourseSubjectStudentPeriods());
          $quaterly_max       =($quaterly_max > $course_subject_student->countCourseSubjectStudentMarks())?$quaterly_max:$course_subject_student->countCourseSubjectStudentMarks();
          $quaterly_period_max=($quaterly_period_max > $course_subject_student->countCourseSubjectStudentPeriods())?$quaterly_period_max:$course_subject_student->countCourseSubjectStudentPeriods();
          break;
        case 3:
          $course_subject_per_type['BIMESTER'][] = $course_subject_student;
          $course_subject_per_type['BIMESTER']['marks'] = max($bimester_max, $course_subject_student->countCourseSubjectStudentMarks());
          $course_subject_per_type['BIMESTER']['periods'] = max($bimester_period_max, $course_subject_student->countCourseSubjectStudentPeriods());
          $bimester_max       =($bimester_max > $course_subject_student->countCourseSubjectStudentMarks())?$bimester_max:$course_subject_student->countCourseSubjectStudentMarks();
          $bimester_period_max=($bimester_period_max > $course_subject_student->countCourseSubjectStudentPeriods())?$bimester_period_max:$course_subject_student->countCourseSubjectStudentPeriods();
        break;
      }
    }
    return $course_subject_per_type;
  }

  /**
   * Returns the status as string.
   *
   * @return string
   */
  public function getStatusString()
  {
    $css = CareerStudentStatus::getInstance("StudentCareerSchoolYearStatus");

    return $css->getStringFor($this->getStatus());
  }

  public function isInCourse()
  {
    return $this->getStatus() == StudentCareerSchoolYearStatus::IN_COURSE;
  }

  public function isApproved()
  {
    return $this->getStatus() == StudentCareerSchoolYearStatus::APPROVED;
  }

  public function isRepproved()
  {
    return $this->getStatus() == StudentCareerSchoolYearStatus::REPPROVED;
  }

  public function suggestYear()
  {
    return $this->isApproved() ? $this->getYear() + 1 : $this->getYear();
  }

  /**
   * Returns the anual average.
   *
   * @return float
   */
  public function getAnualAverage()
  {
    return SchoolBehaviourFactory::getEvaluatorInstance()->getAnualAverageForStudentCareerSchoolYear($this);
  }


  /**
  * This method returns the average of marks for the mark_number and de course_type
  *
  * @return string
  */
  public function getAverageFor($mark_number, $course_type)
  {        
    $course_subject_students = SchoolBehaviourFactory::getInstance()->getCourseSubjectStudentsForCourseType($this->getStudent(), $course_type, $this->getSchoolYear());
    $avg = 0;

    foreach ($course_subject_students as $course_subject_student) 
    {
      $course_subject_student_mark = $course_subject_student->getMarkFor($mark_number);

      if (!$course_subject_student_mark->getIsClosed())
      {
        return '';
      }

      $avg = $course_subject_student_mark->getMark() + $avg;
    }   

    $avg = $avg / count($course_subject_students);

    $avg = sprintf('%.4s', $avg);
    return $avg;
  }

  public function setFree()
  {
    $this->setIsFree(true);
    $this->save();
  }

  public function getIsRepproved()
  {
    return $this->getStatus() == StudentCareerSchoolYearStatus::REPPROVED || $this->getStatus() == StudentCareerSchoolYearStatus::LAST_YEAR_REPPROVED;
  }

  public function isLastYear()
  {
    return $this->getYear() == $this->getCareerSchoolYear()->getCareer()->getQuantityYears();
  }

  public function getSchoolYear()
  {
    return $this->getCareerSchoolYear()->getSchoolYear();
  }

  public function isAbsenceForPeriod()
  {
    return $this->getCareerSchoolYear()->getIsAbsenceForPeriodInYear($this->getYear());
  }

  public function getMaxAbsenceForPeriod($period)
  {
    return $this->getCareerSchoolYear()->getMaxAbsenceForPeriod($period,$this->getYear());
  }

  public function getDivisions()
  {
    $c = new Criteria();
    $c->add(DivisionPeer::CAREER_SCHOOL_YEAR_ID, $this->getCareerSchoolYearId());
    $c->addJoin(DivisionPeer::ID, DivisionStudentPeer::DIVISION_ID);
    $c->add(DivisionStudentPeer::STUDENT_ID, $this->getStudentId());

    return DivisionPeer::doSelect($c);
  }
}