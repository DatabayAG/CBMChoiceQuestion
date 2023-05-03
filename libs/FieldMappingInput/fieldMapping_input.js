document.addEventListener("DOMContentLoaded", function (event) {
  let fieldMappingInputIds = [];
  /**
   * Initializes the existing buttons on page load.
   */
  let init = () => {
    document.querySelectorAll(".fieldMapping_input").forEach((element) => {
      fieldMappingInputIds.push(element.id);
    })

    fieldMappingInputIds.forEach((id) => {
      document.querySelectorAll(`#${id} .fieldMapping_row`).forEach(element => {
        element.querySelector("button[name=add]").addEventListener("click", addRow);
        element.querySelector("button[name=remove]").addEventListener("click", removeRow);
      })
    })
  }

  /**
   * Adds a new row below the previous one.
   * @param event
   */
  let addRow = (event) => {
    let row = event.target.parentNode.parentNode;
    let clone = row.cloneNode(true);
    row.after(clone);
    clone.querySelector("button[name=add]").addEventListener("click", addRow);
    clone.querySelector("button[name=remove]").addEventListener("click", removeRow);

    rebuildRowIds();
  }

  /**
   * Removes the row
   * @param event
   */
  let removeRow = (event) => {
    let row = event.target.parentNode.parentNode;
    let rows = row.parentNode.querySelectorAll(".fieldMapping_row");
    if (rows.length > 1) {
      row.remove();
    }
    rebuildRowIds();
  }

  let rebuildRowIds = () => {
    fieldMappingInputIds.forEach((id) => {
      document.querySelectorAll(`#${id} .fieldMapping_row`).forEach((row, index) => {
        let inputs = row.querySelectorAll(`[name^=${id}]`);
        inputs.forEach((input) => {
          input.name = input.name.replace(/\[\d\]/ig, `[${index}]`);
          input.id = input.id.replace(/_\d_/ig, `_${index}_`);
        })
      });
    })
  }

  il.Util.addOnLoad(init);
});