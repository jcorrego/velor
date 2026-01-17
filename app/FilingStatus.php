<?php

namespace App;

enum FilingStatus: string
{
    case Planning = 'planning';
    case InReview = 'in_review';
    case Filed = 'filed';
}
