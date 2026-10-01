from state_machine import StateMachine, MachineState


machine = StateMachine()

print(f"Initial state: {machine.get_state().value}")

machine.transition_to(MachineState.VALIDATING)
machine.transition_to(MachineState.HEATING)
machine.transition_to(MachineState.DISPENSING)
machine.transition_to(MachineState.DONE)
machine.transition_to(MachineState.IDLE)

print(f"Final state: {machine.get_state().value}")