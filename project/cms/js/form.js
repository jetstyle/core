/*
    Rocket/Forms supply.
    see http://in.jetstyle.ru/rocket/rocketforms
    ------------------------------------------
*/

// универсальный переключатель "свёрнутости" группы
function formsFlipGroup( groupId )
{
  var done = false;
  var collapsed = " collapsed-";
  var group = document.getElementById( groupId );
  if (group)
  {
     if (document.formsFlipTabbed)
       if (document.formsFlipChilds[ groupId ])
       {
         var parent = document.formsFlipChilds[ groupId ];
         for( var i in document.formsFlipParents[parent] )
           if (document.formsFlipParents[parent][i] != groupId)
             formsFlipGroupCollapse( document.formsFlipParents[parent][i], collapsed );

         formsFlipGroupCollapse( groupId, "" );
         done=true;
       }

     if (!done)
     {
       if   (group.className == "collapsable- collapsed-") formsFlipGroupCollapse( groupId, "" );
       else 
       if   (group.className == "collapsable-")            formsFlipGroupCollapse( groupId, collapsed );
     }
  }

  return false;
}
function formsFlipGroupCollapse( groupId, add )
{
  var group = document.getElementById( groupId );
  if (group)
    group.className = "collapsable-"+add;
}
// привязка группы-таба в группу-таб-контрол
function formsFlipSetParent( parentId, childId )
{
  if (!document.formsFlipTabbed)
  {
    document.formsFlipTabbed  = true; 
    document.formsFlipChilds  = new Array();
    document.formsFlipParents = new Array();
  }
  document.formsFlipChilds[ childId ] = parentId;

  if (!document.formsFlipParents[ parentId ])
    document.formsFlipParents[ parentId ] = new Array();

  document.formsFlipParents[ parentId ][ document.formsFlipParents[ parentId ].length ] = childId;
}