<?php

return [
    'research' => [
        /*
         * Provisional development value.
         * The final duration must be approved by the adviser.
         */
        'post_test_duration_minutes' => (int) env(
            'CITIRX_POST_TEST_DURATION_MINUTES',
            72,
        ),

        'post_test_form_version' => env(
            'CITIRX_POST_TEST_FORM_VERSION',
            'post_test_b_v1',
        ),

        /*
         * This may only be used locally or during automated tests.
         * It must be false during actual research deployment.
         */
        'allow_unvalidated_forms' => (bool) env(
            'CITIRX_ALLOW_UNVALIDATED_RESEARCH_FORMS',
            false,
        ),
    ],
];
