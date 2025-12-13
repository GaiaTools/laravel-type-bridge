<?php

return [
    'with_to_string' => new class {
        public function __toString(): string
        {
            return 'stringable';
        }
    },
    'plain_object' => new class {
        // No __toString
    },
];
