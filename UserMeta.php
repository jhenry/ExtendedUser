<?php

class UserMeta extends Model
{
  /**
   * @var int
   */
  public $meta_id;

  /**
   * @var int
   */
  public $user_id;

  /**
   * @var string
   */
  public $meta_key;

  /**
   * @var string
   */
  public $meta_value;
}
