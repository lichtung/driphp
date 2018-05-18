  |  Associativity   |  Operators   |  Additional Information   | 
  | ------------  | ------------  | ------------  | 
  | non-associative   |  clone new  |  clone and new  | 
  | left   |  [  |  array()  | 
  | right   |  **  |  arithmetic  | 
  | right   |  ++ -- ~ (int) (float) (string) (array) (object) (bool) @  |  types and increment/decrement  | 
  | non-associative  |  instanceof  |  types  | 
  | right  |  !  |  logical  | 
  | left  |  * / %  |  arithmetic  | 
  | left  |  + - .  |  arithmetic and string  | 
  | left  |  << >>  |  bitwise  | 
  | non-associative  |  < <= > >=  |  comparison  | 
  | non-associative  |  == != === !== <> <=>  |  comparison  | 
  | left  |  &  |  bitwise and references  | 
  | left  |  ^  |  bitwise  | 
  | left  |    \|    |  bitwise  | 
  | left  |  &&  |  logical  | 
  | left  |    \|\|    |  logical  | 
  | right  |  ??  |  comparison  | 
  | left  |  ? :  |  ternary  | 
  | right  |  = += -= *= **= /= .= %= &= \|= ^= <<= >>=  |  assignment  | 
  | left  |  and  |  logical  | 
  | left  |  xor  |  logical  | 
  | left  |  or  |  logical  | 
  
  
  ### Ternary Operator
  Another conditional operator is the "?:" (or ternary) operator.
  The expression (expr1) ? (expr2) : (expr3) evaluates to expr2 if expr1 evaluates to TRUE, and expr3 if expr1 evaluates to FALSE.
  Since PHP 5.3, it is possible to leave out the middle part of the ternary operator. Expression expr1 ?: expr3 returns expr1 if expr1 evaluates to TRUE, and expr3 otherwise.