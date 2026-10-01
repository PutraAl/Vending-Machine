from enum import Enum


class MachineState(Enum):
    IDLE = "IDLE"
    VALIDATING = "VALIDATING"
    HEATING = "HEATING"
    DISPENSING = "DISPENSING"
    DONE = "DONE"
    ERROR = "ERROR"


class StateMachine:
    def __init__(self):
        self.state = MachineState.IDLE

    def transition_to(self, new_state):
        print(
            f"[STATE] {self.state.value} → {new_state.value}"
        )

        self.state = new_state

    def get_state(self):
        return self.state