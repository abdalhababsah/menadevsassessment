export type RlhfFormFieldDef = {
    id: number;
    stage?: 'pre_prompt' | 'post_prompt' | 'post_rewrite';
    field_key: string;
    label: string;
    description: string | null;
    field_type: 'radio' | 'multi_select' | 'text' | 'textarea' | 'dropdown' | string;
    options: string[] | null;
    required: boolean;
    min_length: number | null;
    position: number;
};

export type RlhfCriterionDef = {
    id: number;
    name: string;
    description: string | null;
    scale_type: string;
    scale_labels: Record<string, string>;
    justification_required_when: Array<number | string>;
    position: number;
};

export type RlhfConfig = {
    number_of_turns: number;
    candidate_input_mode: string;
    model_a: string;
    model_b: string;
    enable_pre_prompt_form: boolean;
    enable_post_prompt_form: boolean;
    enable_rewrite_step: boolean;
    enable_post_rewrite_form: boolean;
    guidelines_markdown: string | null;
};

export type RlhfEvaluation = {
    criterion_id: number;
    response_side?: 'a' | 'b';
    rating_value: string;
    justification: string | null;
};

export type RlhfFormResponse = {
    stage: string;
    field_key: string;
    value: string;
};

export type RlhfTurn = {
    id: number;
    turn_number: number;
    candidate_input: string | null;
    candidate_input_audio_url: string | null;
    response_a: string | null;
    response_b: string | null;
    model_a: string;
    model_b: string;
    generation_status: 'pending' | 'generating' | 'ready' | 'failed';
    generation_error: string | null;
    generated_at: string | null;
    sxs_rating: number | null;
    sxs_justification: string | null;
    selected_side: 'a' | 'b' | null;
    selected_response_rewrite: string | null;
    rewrite_completed_at: string | null;
    completed_at: string | null;
    evaluations: RlhfEvaluation[];
    form_responses: RlhfFormResponse[];
};

export type RlhfState = {
    answer: { id: number; status: string };
    question: {
        id: number;
        stem: string;
        instructions: string | null;
        config: RlhfConfig | null;
        criteria: RlhfCriterionDef[];
        form_fields: RlhfFormFieldDef[];
    };
    turns: RlhfTurn[];
};
