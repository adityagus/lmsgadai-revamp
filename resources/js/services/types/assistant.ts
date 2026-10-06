export interface AssistantResponse {
    choices: {
        message: {
            role: string;
            content: string;
        };
    }[];
}
