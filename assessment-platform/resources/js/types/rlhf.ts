export type RlhfFormStage = 'pre_prompt' | 'post_prompt' | 'post_rewrite';
export type ResponseSide = 'a' | 'b';

export interface RlhfCriterion {
    id: number;
    name: string;
    description: string;
    scale_type: string;
    scale_labels: Record<string, string>;
    justification_required_when: Array<number | string>;
    position: number;
}

export interface RlhfFormField {
    id: number;
    field_key: string;
    label: string;
    description: string | null;
    field_type: string;
    options: string[] | null;
    required: boolean;
    min_length: number | null;
    position: number;
}

export interface RlhfTurnCounter {
    completed: number;
    total: number;
}

export interface RlhfTurn {
    id: number;
    turn_number: number;
    candidate_input: string | null;
    candidate_input_audio_url: string | null;
    response_a: string | null;
    response_b: string | null;
    model_a: string;
    model_b: string;
    generation_status: string;
    generation_error: string | null;
    generated_at: string | null;
    sxs_rating: number | null;
    sxs_justification: string | null;
    selected_side: ResponseSide | null;
    selected_response_rewrite: string | null;
    rewrite_completed_at: string | null;
    completed_at: string | null;
    form_responses: Record<RlhfFormStage, Record<string, unknown>>;
    evaluations: Record<ResponseSide, Record<string, {
        criterion_id: number;
        rating_value: string;
        justification: string | null;
    }>>;
    generation: Record<ResponseSide, {
        status: string;
        error: string | null;
    }>;
    counters: {
        pre_prompt: RlhfTurnCounter;
        post_prompt: RlhfTurnCounter;
        post_rewrite: RlhfTurnCounter;
        evaluate_a: RlhfTurnCounter;
        evaluate_b: RlhfTurnCounter;
    };
}

export interface RlhfState {
    quiz: {
        id: number;
        title: string;
    };
    question: {
        id: number;
        stem: string;
        instructions: string | null;
        guidelines_markdown: string | null;
        number_of_turns: number;
        candidate_input_mode: string;
        enable_pre_prompt_form: boolean;
        enable_post_prompt_form: boolean;
        enable_rewrite_step: boolean;
        enable_post_rewrite_form: boolean;
        criteria: RlhfCriterion[];
        form_fields: Record<RlhfFormStage, RlhfFormField[]>;
    };
    turns: RlhfTurn[];
    current_turn: RlhfTurn | null;
    current_step: string;
    progress: {
        current_turn: number | null;
        completed_turns: number;
        total_turns: number;
    };
    question_completed: boolean;
}
