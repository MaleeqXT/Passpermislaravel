import { MonitorType, StudentType } from './user';

i;
interface Training {
    automatic: boolean;
    adapted_vehicle: boolean;
    accompanied_driving: boolean;
    manual: boolean;
    supervised_driving: boolean;
}

interface License {
    am: boolean;
    b1: boolean;
    a1: boolean;
}

interface Driving {
    already_driven: boolean;
    with_parents: boolean;
    with_driving_school: boolean;
}

interface OtherVehicles {
    moped: boolean;
    bike: boolean;
    tractor: boolean;
    cart: boolean;
    quad: boolean;
    lawnmower: boolean;
    trolley: boolean;
}

interface Experience {
    license: License;
    driving: Driving;
    other_vehicles: OtherVehicles;
}

interface VehicleKnowledge {
    steering: boolean;
    clutch: boolean;
    gearbox: boolean;
    braking: boolean;
}

interface Attitude {
    master_car_and_code: boolean;
    inevitable_step: boolean;
    anticipate_difficulties: boolean;
    desire_to_do_it: boolean;
}

interface Skills {
    vehicle_setup: string;
    steering_wheel: string;
    starting: string;
    stopping: string;
}

interface Understanding {
    comprehension: number;
    restitution: number;
}

interface Environment {
    trajectory: number;
    orientation: number;
    observation: number;
    look: number;
}

interface Emotions {
    relationship: number;
    tension: number;
}

interface Results {
    score: number;
    lessons_proposed: number;
    proposal_accepted: string;
    done_on: string;
    parent_signature: string;
    student_signature: string;
    monitor_signature: string;
}

export type EvaluationType = {
    id: string;
    student_id: string;
    monitor_id: string;
    deleted_at: string | null;
    created_at: string;
    updated_at: string;
    student?: StudentType;
    monitor?: MonitorType;
    data: {
        student: {
            wears_correction: boolean;
            visual_acuity: string;
        };
        training: Training;
        experience: Experience;
        vehicle_knowledge: VehicleKnowledge;
        attitude: Attitude;
        skills: Skills;
        understanding: Understanding;
        environment: Environment;
        emotions: Emotions;
        results: Results;
    };
};
