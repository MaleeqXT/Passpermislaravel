export type CompetencyType = {
    id: string;
    label: string;
    status: boolean;
    main_competency_id: string;
    position: number;
    created_at: string;
    updated_at: string;
    rating: any;
};

export type CompetencyGroupType = {
    id: string;
    status: boolean;
    name: string;
    position: number;
    label: string;
    created_at: string;
    updated_at: string;
    competencies: CompetencyType[];
    competencies_count: number;
    competencies_done_count: number;
};
